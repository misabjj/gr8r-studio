<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;

it('renders the workspace overview through Inertia with project and task data', function () {
    $this->withoutVite();
    $user = User::factory()->create();
    Project::factory()->for($user, 'owner')->create(['name' => 'Website Redesign']);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('id="layer"', false)
        ->assertSee('id="toasts"', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home/Index')
            ->has('projects', 1)
            ->has('tasks')
            ->has('stats.openTasks')
            ->has('stats.completedTasks')
            ->has('stats.projects'));
});

it('redirects guests to login before showing the workspace', function () {
    $this->get('/')->assertRedirect('/login');
});

it('limits workspace data to the signed-in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($otherUser, 'owner')->create();
    $task = Task::factory()->for($project)->create();
    Task::factory()->for($otherProject)->create();

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('stats.projects', 1));
});

it('provides recent completed tasks and per-project completion counts for the workspace', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $completed = Task::factory()->for($project)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('projects.0.tasks_count', 2)
            ->where('projects.0.completed_tasks_count', 1)
            ->where('tasks.0.id', $completed->id));
});

it('counts completed tasks from this week separately from all completed tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    Task::factory()->for($project)->create([
        'status' => TaskStatus::Done,
        'completed_at' => now()->startOfWeek()->addDay(),
    ]);
    Task::factory()->for($project)->create([
        'status' => TaskStatus::Done,
        'completed_at' => now()->startOfWeek()->subSecond(),
    ]);

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.completedTasks', 2)
            ->where('stats.completedThisWeek', 1));
});

it('includes paused projects in the workspace project progress list', function () {
    $user = User::factory()->create();
    $activeProject = Project::factory()->for($user, 'owner')->create(['name' => 'Active']);
    $pausedProject = Project::factory()->for($user, 'owner')->create(['name' => 'Paused', 'status' => 'paused']);
    Project::factory()->for($user, 'owner')->create(['status' => 'completed']);
    Project::factory()->for($user, 'owner')->create(['status' => 'archived']);

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', 2)
            ->where('projects.0.id', $activeProject->id)
            ->where('projects.1.id', $pausedProject->id));
});

it('renders the projects page with task and completion counts', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    Task::factory()->for($project)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)->get('/projects')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Index')
            ->where('projects.0.tasks_count', 2)
            ->where('projects.0.completed_tasks_count', 1));
});

it('renders a project page with its tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['title' => 'Ship the new workspace']);

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('project.id', $project->id)
            ->where('project.tasks.0.id', $task->id));
});

it('queries open and completed task sets by their domain meaning', function () {
    $project = Project::factory()->create();
    $openTask = Task::factory()->for($project)->create(['status' => TaskStatus::Progress]);
    $completedTask = Task::factory()->for($project)->create(['status' => TaskStatus::Done]);

    expect(Task::query()->open()->pluck('id')->all())
        ->toBe([$openTask->id])
        ->and(Task::query()->completed()->pluck('id')->all())
        ->toBe([$completedTask->id]);
});

it('authenticates a user with valid credentials', function () {
    $user = User::factory()->create(['password' => 'correct-horse']);

    $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid login credentials', function () {
    $user = User::factory()->create();

    $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('registers a user and signs them in', function () {
    $this->post('/register', [
        'name' => 'Misa B',
        'email' => 'misa@example.com',
        'password' => 'a-secure-password',
        'password_confirmation' => 'a-secure-password',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'Misa B',
        'email' => 'misa@example.com',
    ]);
});

it('renders the registration page through Inertia', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register'));
});

it('rejects registration when password confirmation does not match', function () {
    $this->from('/register')->post('/register', [
        'name' => 'Misa B',
        'email' => 'misa@example.com',
        'password' => 'a-secure-password',
        'password_confirmation' => 'a-different-password',
    ])->assertRedirect('/register')->assertSessionHasErrors('password');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'misa@example.com']);
});

it('rejects registration passwords that do not meet the Gr8r password rule', function (string $password) {
    $this->from('/register')->post('/register', [
        'name' => 'Misa B',
        'email' => 'misa@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertRedirect('/register')->assertSessionHasErrors('password');

    $this->assertGuest();
})->with(['short1abc' => 'short1abc', 'onlyletters' => 'onlyletters']);

it('accepts a registration password with ten characters and a symbol', function () {
    $this->post('/register', [
        'name' => 'Misa B',
        'email' => 'misa@example.com',
        'password' => 'abcdefgh!j',
        'password_confirmation' => 'abcdefgh!j',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
});

it('sends a password reset link for a registered email address', function () {
    Notification::fake();
    User::factory()->create(['email' => 'misa@example.com']);

    $this->postJson('/forgot-password', ['email' => 'misa@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If an account exists for that email, reset instructions will be sent.');

    Notification::assertSentTo(User::query()->where('email', 'misa@example.com')->first(), ResetPassword::class);
});

it('does not reveal whether an email address has an account', function () {
    Notification::fake();

    $this->postJson('/forgot-password', ['email' => 'unknown@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If an account exists for that email, reset instructions will be sent.');

    Notification::assertNothingSent();
});

it('renders the password reset page with its token and email', function () {
    $user = User::factory()->create(['email' => 'misa@example.com']);
    $token = Password::createToken($user);

    $this->get("/reset-password/{$token}?email=misa%40example.com")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/ResetPassword')
            ->where('token', $token)
            ->where('email', 'misa@example.com'));
});

it('rejects a password reset with an invalid token', function () {
    $user = User::factory()->create(['email' => 'misa@example.com']);

    $this->from('/reset-password/invalid?email=misa%40example.com')
        ->post('/reset-password', [
            'token' => 'invalid',
            'email' => 'misa@example.com',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ])
        ->assertRedirect('/reset-password/invalid?email=misa%40example.com')
        ->assertSessionHasErrors('email');

    expect($user->refresh()->password)->not->toBe('a-new-secure-password');
});

it('resets a password with a valid token', function () {
    $user = User::factory()->create(['email' => 'misa@example.com']);
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'misa@example.com',
        'password' => 'a-new-secure-password',
        'password_confirmation' => 'a-new-secure-password',
    ])->assertRedirect('/login');

    $this->assertGuest();
    $this->post('/login', ['email' => 'misa@example.com', 'password' => 'a-new-secure-password'])
        ->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('returns the updated task as JSON for an asynchronous action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($user)
        ->patch("/tasks/{$task->id}", ['status' => 'done'], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('id', $task->id)
        ->assertJsonPath('status', 'done')
        ->assertJsonStructure(['completed_at']);
});

it('creates a project from validated workspace input', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/projects', [
            'name' => 'Website Redesign',
            'key' => 'WEB',
            'description' => 'Refresh the public website.',
        ])
        ->assertRedirect();

    expect(Project::query()->where('name', 'Website Redesign')->first())
        ->not->toBeNull()
        ->owner_id->toBe($user->id)
        ->key->toBe('WEB');
});

it('creates tasks through a project action and records the chosen status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->post("/projects/{$project->id}/tasks", [
            'title' => 'Review onboarding flow',
            'status' => 'progress',
            'priority' => 'high',
        ])
        ->assertRedirect();

    expect(Task::query()->where('title', 'Review onboarding flow')->first())
        ->not->toBeNull()
        ->project_id->toBe($project->id)
        ->status->toBe(TaskStatus::Progress)
        ->priority->toBe(TaskPriority::High);
});

it('marks a task complete through the update action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($user)
        ->patch("/tasks/{$task->id}", ['status' => 'done'])
        ->assertRedirect();

    expect($task->refresh())
        ->status->toBe(TaskStatus::Done)
        ->completed_at->not->toBeNull();
});

it('rejects invalid task states without writing data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($user)
        ->patch("/tasks/{$task->id}", ['status' => 'blocked-forever'])
        ->assertSessionHasErrors('status');

    expect($task->refresh()->status)->toBe(TaskStatus::Todo);
});

it('does not allow a user to view or change another users project tasks', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($otherUser)->get("/projects/{$project->id}")->assertNotFound();
    $this->actingAs($otherUser)->patch("/tasks/{$task->id}", ['status' => 'done'])->assertForbidden();
    $this->actingAs($otherUser)->post("/projects/{$project->id}/tasks", ['title' => 'Unauthorized'])->assertForbidden();
});
