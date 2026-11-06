<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskIndexController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WorkspaceActivityController;
use App\Http\Controllers\WorkspaceCalendarController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceFavoritesController;
use App\Http\Controllers\WorkspaceInboxController;
use App\Http\Controllers\WorkspaceMembersController;
use App\Http\Controllers\WorkspaceNotificationReadStateController;
use App\Http\Controllers\WorkspaceNotificationsController;
use App\Http\Controllers\WorkspaceOverviewController;
use App\Http\Controllers\WorkspaceSearchController;
use App\Http\Controllers\WorkspaceTimelineController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [WorkspaceController::class, 'index'])->name('home');
    Route::get('/overview', [WorkspaceOverviewController::class, 'index'])->name('overview');
    Route::get('/activity', [WorkspaceActivityController::class, 'index'])->name('activity.index');
    Route::get('/favorites', [WorkspaceFavoritesController::class, 'index'])->name('favorites.index');
    Route::get('/inbox', [WorkspaceInboxController::class, 'index'])->name('inbox.index');
    Route::get('/members', [WorkspaceMembersController::class, 'index'])->name('members.index');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::get('/tasks', [TaskIndexController::class, 'index'])->name('tasks.index');
    Route::get('/my-tasks', [MyTasksController::class, 'index'])->name('my-tasks.index');
    Route::get('/calendar', [WorkspaceCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/timeline', [WorkspaceTimelineController::class, 'index'])->name('timeline.index');
    Route::get('/notifications', [WorkspaceNotificationsController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [WorkspaceNotificationReadStateController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [WorkspaceNotificationReadStateController::class, 'update'])->name('notifications.read');
    Route::get('/search', [WorkspaceSearchController::class, 'index'])->name('search.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    foreach (SettingsController::sections() as $section) {
        $routeName = $section === '2fa' ? 'method2fa' : $section;

        Route::get("/settings/{$section}", [SettingsController::class, 'show'])
            ->defaults('section', $section)
            ->name("settings.{$routeName}");
    }
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::get('/design-system', [SystemController::class, 'design'])->name('system.design');
    Route::get('/system-states', [SystemController::class, 'states'])->name('system.states');
    Route::get('/help/broken-link', [SystemController::class, 'brokenLink'])->name('help.broken-link');
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'show']);
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::put('projects/{project}/members', [ProjectController::class, 'updateMembers'])->name('projects.members.update');
    Route::patch('projects/{project}/favorite', [ProjectController::class, 'toggleFavorite'])->name('projects.favorite');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('tasks/{task}/archive', [TaskController::class, 'archive'])->name('tasks.archive');
    Route::patch('tasks/{task}/favorite', [TaskController::class, 'toggleFavorite'])->name('tasks.favorite');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
