<?php

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\SettingsController;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('filters archived projects through the not archived scope', function () {
    $user = User::factory()->create();
    $visibleProject = Project::factory()->for($user, 'owner')->create();
    Project::factory()->for($user, 'owner')->create(['status' => ProjectStatus::Archived]);

    expect(Project::ownedBy($user)->notArchived()->pluck('id')->all())
        ->toBe([$visibleProject->id]);
});

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

it('creates a project with its selected details and template starter tasks', function () {
    $owner = User::factory()->create();
    $lead = User::factory()->create();
    Team::query()->create([
        'id' => 'design',
        'name' => 'Design',
        'icon' => 'palette',
        'color' => '#8662C9',
        'description' => 'Product design',
    ]);

    $this->actingAs($owner)->postJson(route('projects.store'), [
        'name' => 'Website Redesign',
        'key' => 'WR',
        'description' => 'Rework the website.',
        'icon' => 'globe',
        'color' => 'violet',
        'team' => 'design',
        'lead_id' => $lead->id,
        'start_date' => '2026-11-06',
        'due_date' => '2026-12-06',
        'template' => 'web',
    ])
        ->assertCreated()
        ->assertJsonPath('icon', 'globe')
        ->assertJsonPath('color', 'violet')
        ->assertJsonPath('team', 'design')
        ->assertJsonPath('lead_id', $lead->id);

    $project = Project::query()->where('key', 'WR')->firstOrFail();
    expect($project->tasks)->toHaveCount(6)
        ->and($project->tasks->first()->title)->toBe('Sitemap & information architecture')
        ->and($project->tasks->first()->status)->toBe(TaskStatus::Todo)
        ->and($project->tasks->first()->priority)->toBe(TaskPriority::High)
        ->and($project->tasks->first()->assignee_id)->toBe($lead->id)
        ->and($project->status->value)->toBe('planning');
});

it('updates project details only for the project owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->patchJson(route('projects.update', $project), [
        'name' => 'Updated project name',
        'description' => 'Updated project description',
        'status' => ProjectStatus::Risk->value,
    ])
        ->assertOk()
        ->assertJsonPath('name', 'Updated project name')
        ->assertJsonPath('description', 'Updated project description')
        ->assertJsonPath('status', ProjectStatus::Risk->value);

    $this->actingAs($otherUser)->patchJson(route('projects.update', $project), [
        'name' => 'Unauthorized name',
    ])->assertForbidden();

    expect($project->refresh()->name)->toBe('Updated project name');
});

it('deletes a project and its tasks only for the project owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($otherUser)->deleteJson(route('projects.destroy', $project))
        ->assertForbidden();

    $this->actingAs($owner)->deleteJson(route('projects.destroy', $project))
        ->assertNoContent();

    expect(Project::query()->whereKey($project->id)->exists())->toBeFalse()
        ->and(Task::query()->whereKey($task->id)->exists())->toBeFalse();
});

it('removes archived seeded projects and their tasks from workspace page data', function () {
    $this->seed(DatabaseSeeder::class);
    $owner = User::query()->where('source_key', 'm1')->firstOrFail();
    $project = Project::query()->where('source_key', 'p1')->firstOrFail();

    $this->actingAs($owner)->patchJson(route('projects.update', $project), [
        'status' => ProjectStatus::Archived->value,
    ])->assertOk();

    $workspaceData = app(GetWorkspaceDemoData::class)->handle($owner);

    expect(collect($workspaceData['projects'])->pluck('id'))->not->toContain('p1')
        ->and(collect($workspaceData['tasks'])->pluck('project'))->not->toContain('p1');
});

it('duplicates a project and resets copied tasks to the to do status', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create([
        'name' => 'Website Redesign',
        'key' => 'WEB',
    ]);
    Task::factory()->for($project)->create([
        'title' => 'Review wireframes',
        'status' => TaskStatus::Review,
        'priority' => TaskPriority::High,
        'labels' => ['design'],
    ]);

    $this->actingAs($otherUser)->postJson(route('projects.duplicate', $project))
        ->assertForbidden();

    $this->actingAs($owner)->postJson(route('projects.duplicate', $project))
        ->assertCreated()
        ->assertJsonPath('name', 'Website Redesign copy')
        ->assertJsonPath('status', ProjectStatus::Planning->value);

    $copy = Project::query()->where('name', 'Website Redesign copy')->firstOrFail();

    expect($copy->key)->not->toBe('WEB')
        ->and($copy->tasks)->toHaveCount(1)
        ->and($copy->tasks->first()->title)->toBe('Review wireframes')
        ->and($copy->tasks->first()->status)->toBe(TaskStatus::Todo)
        ->and($copy->tasks->first()->priority)->toBe(TaskPriority::High)
        ->and($copy->tasks->first()->labels)->toBe(['design']);
});

it('syncs project members only for the project owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->putJson(route('projects.members.update', $project), [
        'members' => [$member->id],
    ])->assertOk()->assertJsonFragment(['id' => $member->id]);

    $this->actingAs($otherUser)->putJson(route('projects.members.update', $project), [
        'members' => [$otherUser->id],
    ])->assertForbidden();

    expect($project->members()->pluck('users.id')->all())->toBe([$owner->id, $member->id]);
});

it('updates task subtasks through the task update action', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create([
        'subtasks' => [
            ['id' => 'sub-1', 'title' => 'Review empty states', 'done' => false],
        ],
    ]);

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'subtasks' => [
            ['id' => 'sub-1', 'title' => 'Review empty states', 'done' => true],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('subtasks.0.done', true);

    expect($task->refresh()->subtasks[0]['done'])->toBeTrue();
});

it('moves a task to another project owned by the same user', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $destination = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'project_id' => $destination->id,
    ])
        ->assertOk()
        ->assertJsonPath('project_id', $destination->id);

    expect($task->refresh()->project_id)->toBe($destination->id);
});

it('does not move a task into a project owned by another user', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $otherProject = Project::factory()->for($otherOwner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'project_id' => $otherProject->id,
    ])->assertUnprocessable();

    expect($task->refresh()->project_id)->toBe($project->id);
});

it('archives a task without deleting it and excludes it from active task queries', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($owner)->patchJson(route('tasks.archive', $task))
        ->assertOk()
        ->assertJsonPath('is_archived', true);

    expect(Task::query()->find($task->id))->not->toBeNull()
        ->and(Task::ownedBy($owner)->notArchived()->pluck('id')->all())->toBe([]);
});

it('prevents users from archiving tasks owned by someone else', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($otherOwner)->patchJson(route('tasks.archive', $task))->assertForbidden();

    expect($task->refresh()->is_archived)->toBeFalse();
});

it('permanently deletes a task owned by the current user', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($owner)->deleteJson(route('tasks.destroy', $task))->assertNoContent();

    expect(Task::query()->find($task->id))->toBeNull();
});

it('prevents users from deleting tasks owned by someone else', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($otherOwner)->deleteJson(route('tasks.destroy', $task))->assertForbidden();

    expect($task->refresh())->not->toBeNull();
});

it('restores an archived task to active task queries', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($owner)->patchJson(route('tasks.archive', $task))->assertOk();
    $this->actingAs($owner)->patchJson(route('tasks.restore', $task))->assertOk()
        ->assertJsonPath('is_archived', false);

    expect(Task::ownedBy($owner)->notArchived()->pluck('id')->all())->toBe([$task->id]);
});

it('duplicates a task without copying its favorite state', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Write release notes',
        'description' => 'Summarize changes for the release.',
        'is_favorite' => true,
        'subtasks' => [['id' => 'sub-1', 'title' => 'List changes', 'done' => true]],
    ]);

    $this->actingAs($owner)->postJson(route('tasks.duplicate', $task))
        ->assertCreated()
        ->assertJsonPath('title', 'Write release notes (copy)')
        ->assertJsonPath('description', 'Summarize changes for the release.')
        ->assertJsonPath('is_favorite', false)
        ->assertJsonPath('subtasks.0.title', 'List changes');

    expect($project->tasks()->count())->toBe(2);
});

it('renders the original settings sections through Inertia', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/settings/appearance')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Appearance')
            ->where('section', 'appearance'));
});

it('gives every settings section its own page and named route', function () {
    $user = User::factory()->create();
    $pages = [
        'workspace' => 'Workspace', 'appearance' => 'Appearance', 'language' => 'Language',
        'datetime' => 'Datetime', 'members' => 'Members', 'teams' => 'Teams', 'projects' => 'Projects',
        'permissions' => 'Permissions', 'notif-email' => 'NotifEmail', 'notif-push' => 'NotifPush',
        'notif-mentions' => 'NotifMentions', 'notif-assign' => 'NotifAssign', 'profile' => 'Profile',
        'preferences' => 'Preferences', 'shortcuts' => 'Shortcuts', 'password' => 'Password',
        'sessions' => 'Sessions', '2fa' => 'TwoFactor', 'plan' => 'Plan', 'payment' => 'Payment',
        'invoices' => 'Invoices',
    ];

    foreach ($pages as $section => $pageName) {
        $routeName = $section === '2fa' ? 'settings.method2fa' : "settings.{$section}";

        expect(route($routeName))->toEndWith("/settings/{$section}");

        $this->actingAs($user)->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component("Settings/{$pageName}")
                ->where('section', $section));
    }
});

it('redirects settings index to its selected named section route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/settings')->assertRedirect('/settings/appearance');
    $this->actingAs($user)->get('/settings?sec=teams')->assertRedirect('/settings/teams');
    $this->actingAs($user)->get('/settings?sec=2fa')->assertRedirect('/settings/2fa');
    $this->actingAs($user)->get('/settings?sec=missing')->assertNotFound();
    $this->actingAs($user)->get('/settings?sec[]=teams')->assertNotFound();

    expect(fn () => app(SettingsController::class)->show('missing'))
        ->toThrow(HttpException::class);
});

it('renders the original members workspace data through Inertia', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/members')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Members/Index')
            ->has('workspaceData.members', 8)
            ->has('teams'));
});

it('omits private projects and their tasks when the current member lacks access', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/members')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Members/Index')
            ->where('workspaceData.projects', fn (Collection $projects): bool => $projects->pluck('id')->contains('p6'))
            ->where('workspaceData.tasks', fn (Collection $tasks): bool => $tasks->pluck('id')->doesntContain('t49')));
});

it('includes database member ids in workspace data for task assignment', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();
    $assignee = User::query()->where('source_key', 'm2')->firstOrFail();

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home/Index')
            ->where('workspaceData.members.1.database_id', $assignee->id));
});

it('keeps the seeded My Tasks statuses aligned with the original tasks page', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $response = $this->actingAs($user)->get('/my-tasks')->assertOk();
    $tasks = collect($response->viewData('page')['props']['tasks']);

    expect($tasks->firstWhere('source_key', 't6')['status'])->toBe(TaskStatus::Todo->value)
        ->and($tasks->firstWhere('source_key', 't15')['status'])->toBe(TaskStatus::Todo->value)
        ->and($tasks->firstWhere('source_key', 't33')['status'])->toBe(TaskStatus::Todo->value);
});

it('offers only non-completed projects in the My Tasks new task dialog', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/my-tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/MyTasks')
            ->where('projects', fn (Collection $projects): bool => $projects->pluck('name')->contains('Marketing Website') === false));
});

it('renders the original member profile through Inertia', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/members/m2')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Members/Show')
            ->where('memberId', 'm2')
            ->has('workspaceData.members', 8));
});

it('shares original project saved views with the project page', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();
    $project = Project::query()->where('source_key', 'p1')->firstOrFail();

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('workspaceData.savedViews.0.id', 'v1')
            ->where('workspaceData.savedViews.0.project', 'p1')
            ->where('workspaceData.savedViews.0.name', 'High priority')
            ->where('workspaceData.savedViews.0.filters.0.v', ['urgent', 'high']));
});

it('renders the original team profile through Inertia', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/teams/design')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Teams/Show')
            ->where('team.id', 'design')
            ->has('workspaceData.members', 8));
});

it('updates a team profile and moves selected members through the team action route', function () {
    $this->seed(DatabaseSeeder::class);
    $owner = User::query()->where('source_key', 'm1')->firstOrFail();
    $team = Team::query()->whereKey('design')->firstOrFail();
    $selectedMember = User::query()->where('source_key', 'm2')->firstOrFail();
    $formerMember = User::query()->where('source_key', 'm4')->firstOrFail();

    $this->actingAs($owner)->patchJson(route('teams.update', $team), [
        'name' => 'Design & Research',
        'description' => 'Product design and research',
        'icon' => 'sparkles',
        'color' => 'teal',
        'member_ids' => [$selectedMember->id],
    ])
        ->assertOk()
        ->assertJsonPath('name', 'Design & Research')
        ->assertJsonPath('description', 'Product design and research')
        ->assertJsonPath('icon', 'sparkles')
        ->assertJsonPath('color', '#23918A');

    expect($team->refresh()->only(['name', 'description', 'icon', 'color']))
        ->toBe([
            'name' => 'Design & Research',
            'description' => 'Product design and research',
            'icon' => 'sparkles',
            'color' => '#23918A',
        ])
        ->and($selectedMember->refresh()->team)->toBe('design')
        ->and($formerMember->refresh()->team)->toBeNull();
});

it('creates a team with selected members through the team action route', function () {
    $this->seed(DatabaseSeeder::class);
    $owner = User::query()->where('source_key', 'm1')->firstOrFail();
    $member = User::query()->where('source_key', 'm2')->firstOrFail();

    $this->actingAs($owner)->postJson(route('teams.store'), [
        'name' => 'Research',
        'description' => 'Customer research and testing',
        'icon' => 'users',
        'color' => 'teal',
        'member_ids' => [$member->id],
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'Research')
        ->assertJsonPath('description', 'Customer research and testing')
        ->assertJsonPath('icon', 'users')
        ->assertJsonPath('color', '#23918A');

    $team = Team::query()->where('name', 'Research')->firstOrFail();
    expect($team->id)->toStartWith('tm_')
        ->and($member->refresh()->team)->toBe($team->id);
});

it('deletes a team and clears its member and project assignments through the team action route', function () {
    $this->seed(DatabaseSeeder::class);
    $owner = User::query()->where('source_key', 'm1')->firstOrFail();
    $team = Team::query()->whereKey('design')->firstOrFail();

    $this->actingAs($owner)->deleteJson(route('teams.destroy', $team))
        ->assertNoContent();

    expect(Team::query()->whereKey('design')->exists())->toBeFalse()
        ->and(User::query()->where('team', 'design')->exists())->toBeFalse()
        ->and(Project::query()->where('team', 'design')->exists())->toBeFalse();
});

it('denies team management to members without an administrative role', function () {
    $this->seed(DatabaseSeeder::class);
    $member = User::query()->where('source_key', 'm3')->firstOrFail();
    $team = Team::query()->whereKey('design')->firstOrFail();

    $this->actingAs($member)->patchJson(route('teams.update', $team), [
        'name' => 'Design',
        'description' => 'Product design, brand, and research',
        'icon' => 'palette',
        'color' => 'violet',
        'member_ids' => [],
    ])->assertForbidden();

    $this->actingAs($member)->deleteJson(route('teams.destroy', $team))->assertForbidden();
});

it('does not derive task creation dialog state from the assignee query parameter', function () {
    $this->seed(DatabaseSeeder::class);
    $owner = User::query()->where('source_key', 'm1')->firstOrFail();
    $this->actingAs($owner)->get('/tasks?assignee=m2')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/Index')
            ->missing('defaultAssigneeId')
            ->has('members', 8));
});

it('provides only visible, non-archived project details in original order for task creation', function () {
    $user = User::factory()->create();
    $firstProject = Project::factory()->for($user, 'owner')->create([
        'name' => 'First project',
        'status' => ProjectStatus::Active,
    ]);
    $privateProject = Project::factory()->for($user, 'owner')->create([
        'name' => 'Private project',
        'is_private' => true,
    ]);
    $completedProject = Project::factory()->for($user, 'owner')->create([
        'name' => 'Completed project',
        'status' => ProjectStatus::Complete,
    ]);
    $archivedProject = Project::factory()->for($user, 'owner')->create([
        'name' => 'Archived project',
        'status' => ProjectStatus::Archived,
    ]);

    $this->actingAs($user)->get('/tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/Index')
            ->has('projects', 2)
            ->where('projects', fn (Collection $projects): bool => $projects->pluck('id')->all() === [$firstProject->id, $completedProject->id])
            ->where('projects.0.id', $firstProject->id)
            ->where('projects.0.status', ProjectStatus::Active->value)
            ->where('projects.0.is_private', false)
            ->where('projects.1.status', ProjectStatus::Complete->value));
});

it('shows only tasks in public projects or private projects the user belongs to', function () {
    $user = User::factory()->create();
    $publicProject = Project::factory()->for($user, 'owner')->create();
    $privateProject = Project::factory()->for($user, 'owner')->create(['is_private' => true]);
    $memberPrivateProject = Project::factory()->for($user, 'owner')->create(['is_private' => true]);
    $memberPrivateProject->members()->attach($user);

    $publicTask = Task::factory()->for($publicProject)->create();
    Task::factory()->for($privateProject)->create();
    $memberPrivateTask = Task::factory()->for($memberPrivateProject)->create();

    $this->actingAs($user)->get('/tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/Index')
            ->has('projects', 2)
            ->where('projects', fn (Collection $projects): bool => $projects->pluck('id')->all() === [$publicProject->id, $memberPrivateProject->id])
            ->has('tasks', 2)
            ->where('tasks', fn (Collection $tasks): bool => $tasks->pluck('id')->all() === [$publicTask->id, $memberPrivateTask->id]));
});

it('passes supported task summary filters to the task page', function (string $filter) {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/tasks?filter='.$filter)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/Index')
            ->where('defaultFilter', $filter)
            ->missing('defaultAssigneeId'));
})->with(['open', 'done', 'overdue']);

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

it('includes task assignee details for the home task drawer', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create(['name' => 'Assigned teammate']);
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['assignee_id' => $assignee->id]);

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.assignee.name', 'Assigned teammate'));
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
    $lead = User::factory()->create(['name' => 'Project Lead']);
    $member = User::factory()->create(['name' => 'Project Member']);
    $project = Project::factory()->for($user, 'owner')->create(['lead_id' => $lead->id, 'team' => 'design']);
    $project->members()->attach($member);
    Task::factory()->for($project)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)->get('/projects')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Index')
            ->where('projects.0.tasks_count', 2)
            ->where('projects.0.completed_tasks_count', 1)
            ->where('projects.0.lead.name', 'Project Lead')
            ->where('projects.0.members.0.name', 'Project Member')
            ->where('projects.0.team', 'design'));
});

it('provides project leads in workspace order and excludes invited members', function () {
    $owner = User::factory()->create([
        'name' => 'Tanjim Islam',
        'status' => 'active',
        'source_key' => 'm1',
    ]);
    User::factory()->create([
        'name' => 'Sarah Chen',
        'status' => 'active',
        'source_key' => 'm2',
    ]);
    User::factory()->create([
        'name' => 'John Carter',
        'status' => 'active',
        'source_key' => 'm3',
    ]);
    User::factory()->create([
        'name' => 'Diego Alvarez',
        'status' => 'invited',
        'source_key' => 'm8',
    ]);

    $this->actingAs($owner)->get('/projects')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Index')
            ->has('members', 3)
            ->where('members.0.name', 'Tanjim Islam')
            ->where('members.1.name', 'Sarah Chen')
            ->where('members.2.name', 'John Carter'));
});

it('does not derive the create project dialog from a query parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/projects?create=1')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Index')
            ->missing('openCreateProject'));
});

it('includes project members for the home progress team stack', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $member = User::factory()->create(['name' => 'Team Member']);
    $project->members()->attach($member);

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('projects.0.members.0.name', 'Team Member'));
});

it('renders the workspace overview with projects and task status totals', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['name' => 'Website Redesign']);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Done]);

    $this->actingAs($user)->get('/overview')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Overview')
            ->has('projects', 1)
            ->where('stats.tasks', 2)
            ->where('stats.openTasks', 1)
            ->where('stats.completedTasks', 1)
            ->where('statusCounts.todo', 1)
            ->where('statusCounts.done', 1));
});

it('renders workspace tasks only from the signed-in user projects', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($otherUser, 'owner')->create();
    $task = Task::factory()->for($project)->create(['title' => 'Review homepage']);
    Task::factory()->for($otherProject)->create(['title' => 'Private task']);

    $this->actingAs($user)->get('/tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.title', 'Review homepage'));
});

it('renders My Tasks with only tasks assigned to the signed-in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $assignedTask = Task::factory()->for($project)->create(['assignee_id' => $user->id]);
    Task::factory()->for($project)->create(['assignee_id' => $otherUser->id]);
    Task::factory()->for($project)->create(['assignee_id' => null]);

    $this->actingAs($user)->get('/my-tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/MyTasks')
            ->has('tasks', 1)
            ->where('tasks.0.id', $assignedTask->id));
});

it('keeps workspace calendar events out of My Tasks calendar view', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/my-tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/MyTasks')
            ->where('workspaceData.events', []));
});

it('creates tasks assigned to any workspace member and rejects outsiders', function () {
    $user = User::factory()->create();
    $workspaceMember = User::factory()->create(['source_key' => 'm2']);
    $outsider = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->postJson("/projects/{$project->id}/tasks", [
        'title' => 'Review the launch plan',
        'assignee_id' => $user->id,
    ])->assertCreated()->assertJsonPath('assignee_id', $user->id);

    $this->actingAs($user)->postJson("/projects/{$project->id}/tasks", [
        'title' => 'Review a task with a teammate',
        'assignee_id' => $workspaceMember->id,
    ])->assertCreated()->assertJsonPath('assignee_id', $workspaceMember->id);

    $this->actingAs($user)->postJson("/projects/{$project->id}/tasks", [
        'title' => 'Review a task with an outsider',
        'assignee_id' => $outsider->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('assignee_id');
});

it('updates task assignees to any workspace member and rejects outsiders', function () {
    $owner = User::factory()->create();
    $workspaceMember = User::factory()->create(['source_key' => 'm2']);
    $outsider = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'assignee_id' => $workspaceMember->id,
    ])->assertOk()->assertJsonPath('assignee_id', $workspaceMember->id);

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'assignee_id' => $outsider->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('assignee_id');

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'assignee_id' => null,
    ])->assertOk()->assertJsonPath('assignee_id', null);
});

it('updates task labels, recurrence, estimate, and dependencies', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create(['source_key' => 'task-1']);
    Task::factory()->for($project)->create(['source_key' => 'task-2']);

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'labels' => ['design', 'bug'],
        'recurrence' => 'Weekly',
        'estimate' => '3d',
        'dependencies' => ['task-2'],
    ])->assertOk()
        ->assertJsonPath('labels', ['design', 'bug'])
        ->assertJsonPath('recurrence', 'Weekly')
        ->assertJsonPath('estimate', '3d')
        ->assertJsonPath('dependencies', ['task-2']);

    $this->actingAs($owner)->patchJson(route('tasks.update', $task), [
        'dependencies' => ['missing-task'],
    ])->assertUnprocessable()->assertJsonValidationErrors('dependencies.0');
});

it('creates a task with its description, labels, subtasks, and recurrence', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->postJson("/projects/{$project->id}/tasks", [
        'title' => 'Prepare release notes',
        'description' => 'Document the changes for the release.',
        'labels' => ['design', 'bug'],
        'subtasks' => [
            ['id' => 'subtask-1', 'title' => 'Collect completed work', 'done' => false],
        ],
        'attachments' => [
            ['id' => 'attachment-1', 'name' => 'release-notes.pdf', 'type' => 'pdf', 'size' => '18 KB', 'by' => $user->id, 'at' => '2026-11-05T12:00:00.000Z'],
        ],
        'recurrence' => 'weekly',
    ])->assertCreated()
        ->assertJsonPath('description', 'Document the changes for the release.')
        ->assertJsonPath('labels', ['design', 'bug'])
        ->assertJsonPath('subtasks.0.title', 'Collect completed work')
        ->assertJsonPath('attachments.0.name', 'release-notes.pdf')
        ->assertJsonPath('recurrence', 'weekly');
});

it('stores task and project start and due dates for schedule views', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->postJson("/projects/{$project->id}/tasks", [
        'title' => 'Build the release plan',
        'start_date' => '2026-11-10',
        'due_date' => '2026-11-20',
    ])->assertCreated()->assertJsonPath('start_date', '2026-11-10T00:00:00.000000Z')->assertJsonPath('due_date', '2026-11-20T00:00:00.000000Z');

    $this->actingAs($user)->postJson('/projects', [
        'name' => 'Release planning',
        'key' => 'REL',
        'start_date' => '2026-11-05',
        'due_date' => '2026-12-01',
    ])->assertCreated()->assertJsonPath('start_date', '2026-11-05T00:00:00.000000Z')->assertJsonPath('due_date', '2026-12-01T00:00:00.000000Z');
});

it('renders the workspace calendar and timeline with owner scoped tasks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['name' => 'Zulu', 'source_key' => 'p1']);
    Project::factory()->for($user, 'owner')->create(['name' => 'Alpha', 'source_key' => 'p2']);
    Project::factory()->for($user, 'owner')->create(['name' => 'Private', 'source_key' => 'p3', 'is_private' => true]);
    Project::factory()->for($user, 'owner')->create(['name' => 'Complete', 'source_key' => 'p4', 'status' => ProjectStatus::Complete]);
    Project::factory()->for($user, 'owner')->create(['name' => 'Completed', 'source_key' => 'p5', 'status' => ProjectStatus::Completed]);
    $otherProject = Project::factory()->for($otherUser, 'owner')->create();
    $task = Task::factory()->for($project)->create();
    Task::factory()->for($otherProject)->create();

    foreach ([['/calendar', 'Workspace/Calendar'], ['/timeline', 'Workspace/Timeline']] as [$url, $component]) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($component)
                ->has('tasks', 1)
                ->where('tasks.0.id', $task->id)
                ->when($component === 'Workspace/Calendar', fn (AssertableInertia $page) => $page
                    ->where('projects.0.id', $project->id)
                    ->has('projects', 3)
                    ->where('projects.2.is_private', true)
                    ->has('workspaceData.events'))
                ->when($component === 'Workspace/Timeline', fn (AssertableInertia $page) => $page
                    ->has('workspaceData.projects')));
    }
});

it('renders the source-defined empty workspace pages', function () {
    $user = User::factory()->create();
    foreach ([
        ['/activity', 'Workspace/Activity'],
        ['/favorites', 'Workspace/Favorites'],
        ['/inbox', 'Workspace/Inbox'],
        ['/notifications', 'Workspace/Notifications'],
    ] as [$url, $component]) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }
});

it('provides scoped tasks to notifications so task alerts can open their drawer', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user)->get('/notifications')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Notifications')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id));
});

it('provides scoped tasks to activity so task events can open their drawer', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user)->get('/activity')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Activity')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id));
});

it('provides scoped tasks to inbox so task notifications can open their drawer', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user)->get('/inbox')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Inbox')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id));
});

it('persists notification read state for the signed-in workspace user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('email', 'hello@gr8rstudio.com')->firstOrFail();

    $this->actingAs($user)->patchJson('/notifications/n1/read', ['read' => true])
        ->assertOk()
        ->assertJsonPath('read', true);

    $this->actingAs($user)->get('/notifications')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Notifications')
            ->where('workspaceData.notifs.0.id', 'n1')
            ->where('workspaceData.notifs.0.read', true));

    $this->actingAs($user)->patchJson('/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('count', 10);

    $this->actingAs($user)->get('/inbox')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Inbox')
            ->where('workspaceData.notifs.0.read', true)
            ->where('workspaceData.notifs.1.read', true));
});

it('provides the original workspace feed data to seeded demo pages', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('email', 'hello@gr8rstudio.com')->firstOrFail();
    $workspaceData = app(GetWorkspaceDemoData::class)->handle($user);

    expect(abs(now()->valueOf() - $workspaceData['activity'][0]['at']))
        ->toBeLessThan(40 * 60 * 1000);
    expect(now()->valueOf() - $workspaceData['notifs'][0]['at'])
        ->toBeBetween(3 * 60 * 60 * 1000, 4 * 60 * 60 * 1000);

    $this->actingAs($user)->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home/Index')
            ->has('workspaceData.activity', 13));

    $this->actingAs($user)->get('/activity')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Activity')
            ->has('workspaceData.activity', 13)
            ->has('workspaceData.members', 8));

    $this->actingAs($user)->get('/inbox')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Inbox')
            ->has('workspaceData.notifs', 10)
            ->has('workspaceData.comments', 9));

    $this->actingAs($user)->get('/search?q=homepage')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Search')
            ->has('workspaceData.files', 12)
            ->has('workspaceData.comments', 9)
            ->where('workspaceData.recentSearches.0', 'homepage'));

    $this->actingAs($user)->get('/overview')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Overview')
            ->has('workspaceData.members', 8)
            ->has('workspaceData.projects.0.milestones'));

    $this->actingAs($user)->get('/my-tasks')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/MyTasks')
            ->has('tasks', 8)
            ->has('workspaceData.comments', 9)
            ->has('workspaceData.activity', 13));

    $this->actingAs($user)->get('/tasks')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tasks/Index')
            ->has('workspaceData.comments', 9)
            ->has('workspaceData.activity', 13));

    $sourceProject = Project::query()->where('source_key', 'p1')->firstOrFail();
    $this->actingAs($user)->get("/projects/{$sourceProject->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('activeTab', 'board')
            ->has('project.members', 7)
            ->has('project.lead.name')
            ->has('workspaceData.files', 12)
            ->has('workspaceData.activity', 13));

    $this->actingAs($user)->get("/projects/{$sourceProject->id}?tab=files")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('activeTab', 'files'));
});

it('toggles a project favorite and exposes only the signed-in user favorites', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $member = User::factory()->create();
    $project->members()->attach($member);
    Project::factory()->for($otherUser, 'owner')->create(['is_favorite' => true]);

    $this->actingAs($user)->patchJson("/projects/{$project->id}/favorite")
        ->assertOk()
        ->assertJsonPath('is_favorite', true);

    $this->get('/favorites')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Favorites')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->where('projects.0.members.0.name', $member->name)
            ->where('projects.0.is_favorite', true));
});

it('toggles task favorites and returns only the signed-in users favorite tasks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();
    Task::factory()->for(Project::factory()->for($otherUser, 'owner'))->create(['is_favorite' => true]);

    $this->actingAs($otherUser)->patchJson("/tasks/{$task->id}/favorite")->assertForbidden();

    $this->actingAs($user)->patchJson("/tasks/{$task->id}/favorite")
        ->assertOk()
        ->assertJsonPath('is_favorite', true);

    $this->get('/favorites')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Favorites')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id));

    $this->actingAs($user)->patchJson("/tasks/{$task->id}/favorite")
        ->assertOk()
        ->assertJsonPath('is_favorite', false);
});

it('provides task detail data on favorites for opening the original task drawer', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('source_key', 'm1')->firstOrFail();

    $this->actingAs($user)->get('/favorites')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Favorites')
            ->has('workspaceData.tasks')
            ->has('workspaceData.comments')
            ->has('workspaceData.activity'));
});

it('searches the signed-in workspace projects and tasks by the source search terms', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['name' => 'Website Refresh']);
    $matchingTask = Task::factory()->for($project)->create(['title' => 'Review navigation']);
    Task::factory()->for($project)->create(['title' => 'Write launch copy']);
    Task::factory()->for(Project::factory()->for($otherUser, 'owner'))->create(['title' => 'Private navigation task']);

    $this->actingAs($user)->get('/search?q=navigation')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Workspace/Search')
            ->where('query', 'navigation')
            ->has('projects', 0)
            ->has('tasks', 1)
            ->where('tasks.0.id', $matchingTask->id));
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

it('shows the password reset confirmation on the forgot password page', function () {
    $message = 'If an account exists for that email, reset instructions will be sent.';

    $this->from('/forgot-password')->post('/forgot-password', ['email' => 'unknown@example.com'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status', $message);

    $this->get('/forgot-password')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/ForgotPassword')
            ->where('status', $message));
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

it('authorizes projects and tasks through their policies', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    expect($owner->can('viewAny', Project::class))->toBeTrue()
        ->and($owner->can('view', $project))->toBeTrue()
        ->and($otherUser->can('view', $project))->toBeFalse()
        ->and($owner->can('create', Project::class))->toBeTrue()
        ->and($owner->can('update', $project))->toBeTrue()
        ->and($otherUser->can('update', $project))->toBeFalse()
        ->and($owner->can('viewAny', Task::class))->toBeTrue()
        ->and($owner->can('create', [Task::class, $project]))->toBeTrue()
        ->and($otherUser->can('create', [Task::class, $project]))->toBeFalse()
        ->and($owner->can('update', $task))->toBeTrue()
        ->and($otherUser->can('update', $task))->toBeFalse();
});

it('seeds the complete source dataset without duplicating records', function () {
    $legacyOwner = User::factory()->create(['email' => 'hello@gr8rstudio.com']);
    $legacyProject = Project::factory()->for($legacyOwner, 'owner')->create([
        'name' => 'Website Redesign',
        'key' => 'WEB',
    ]);
    $legacyTask = Task::factory()->for($legacyProject)->create([
        'title' => 'Review the new navigation structure',
    ]);
    $seeder = app(DatabaseSeeder::class);
    $seededAt = now();
    $seeder->run();
    $seeder->run();

    expect(User::query()->count())->toBe(8)
        ->and(Team::query()->count())->toBe(4)
        ->and(Project::query()->count())->toBe(7)
        ->and(Task::query()->count())->toBe(49)
        ->and(User::query()->where('email', 'sarah@gr8rstudio.com')->value('name'))->toBe('Sarah Chen')
        ->and(Project::query()->where('key', 'WEB')->value('name'))->toBe('Website Redesign')
        ->and(Project::query()->where('source_key', 'p1')->value('due_date')->toDateString())->toBe('2026-11-29')
        ->and(Project::query()->where('source_key', 'p5')->firstOrFail()->updated_at->between(
            $seededAt->copy()->subMinutes(301),
            $seededAt->copy()->subMinutes(299),
        ))->toBeTrue()
        ->and(Task::query()->where('source_key', 't3')->value('is_favorite'))->toBeTrue()
        ->and(Task::query()->where('source_key', 't3')->value('labels'))->toBe(['design'])
        ->and(Task::query()->find($legacyTask->id))->toBeNull()
        ->and(DB::table('project_user')->count())->toBe(30);

    $sourceData = json_decode(DB::table('workspace_demo_snapshots')->where('name', 'Gr8r Studio')->value('source_data'), true);

    expect(count($sourceData['comments']))->toBe(9)
        ->and(count($sourceData['activity']))->toBe(13)
        ->and(count($sourceData['notifs']))->toBe(10)
        ->and(count($sourceData['files']))->toBe(12)
        ->and(count($sourceData['events']))->toBe(6)
        ->and(count($sourceData['workspaces']))->toBe(3);
});
