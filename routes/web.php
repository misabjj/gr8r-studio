<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskIndexController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceCalendarController;
use App\Http\Controllers\WorkspaceOverviewController;
use App\Http\Controllers\WorkspaceTimelineController;
use App\Http\Controllers\WorkspaceActivityController;
use App\Http\Controllers\WorkspaceFavoritesController;
use App\Http\Controllers\WorkspaceInboxController;
use App\Http\Controllers\WorkspaceNotificationsController;
use App\Http\Controllers\WorkspaceSearchController;
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
    Route::get('/tasks', [TaskIndexController::class, 'index'])->name('tasks.index');
    Route::get('/my-tasks', [MyTasksController::class, 'index'])->name('my-tasks.index');
    Route::get('/calendar', [WorkspaceCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/timeline', [WorkspaceTimelineController::class, 'index'])->name('timeline.index');
    Route::get('/notifications', [WorkspaceNotificationsController::class, 'index'])->name('notifications.index');
    Route::get('/search', [WorkspaceSearchController::class, 'index'])->name('search.index');
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'show']);
    Route::patch('projects/{project}/favorite', [ProjectController::class, 'toggleFavorite'])->name('projects.favorite');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
});
