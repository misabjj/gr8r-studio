<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceOverviewController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $statusCounts = Task::ownedBy($user)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        return Inertia::render('Workspace/Overview', [
            'projects' => Project::ownedBy($user)
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->withCount('tasks')
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->completed()])
                ->withCount(['tasks as overdue_tasks_count' => fn (Builder $query) => $query->open()->whereDate('due_date', '<', today())])
                ->orderBy('name')
                ->get(),
            'stats' => [
                'projects' => Project::ownedBy($user)->where('status', '!=', ProjectStatus::Archived->value)->count(),
                'completedProjects' => Project::ownedBy($user)->where('status', ProjectStatus::Completed->value)->count(),
                'tasks' => Task::ownedBy($user)->count(),
                'openTasks' => Task::ownedBy($user)->open()->count(),
                'completedTasks' => Task::ownedBy($user)->completed()->count(),
                'overdueTasks' => Task::ownedBy($user)->open()->whereDate('due_date', '<', today())->count(),
            ],
            'statusCounts' => collect(TaskStatus::cases())
                ->mapWithKeys(fn (TaskStatus $status): array => [$status->value => (int) $statusCounts->get($status->value, 0)]),
        ]);
    }
}
