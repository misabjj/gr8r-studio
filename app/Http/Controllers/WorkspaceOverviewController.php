<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
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
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();
        $statusCounts = Task::ownedBy($user)
            ->notArchived()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $assignedStatusCounts = Task::ownedBy($user)
            ->notArchived()
            ->assignedTo($user)
            ->open()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return Inertia::render('Workspace/Overview', [
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->withCount(['tasks' => fn (Builder $query) => $query->notArchived()])
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->notArchived()->completed()])
                ->withCount(['tasks as overdue_tasks_count' => fn (Builder $query) => $query->notArchived()->open()->whereDate('due_date', '<', today())])
                ->orderBy('name')
                ->get(),
            'stats' => [
                'projects' => Project::ownedBy($user)->notArchived()->count(),
                'completedProjects' => Project::ownedBy($user)->whereIn('status', [ProjectStatus::Completed->value, ProjectStatus::Complete->value])->count(),
                'tasks' => Task::ownedBy($user)->notArchived()->count(),
                'openTasks' => Task::ownedBy($user)->notArchived()->open()->count(),
                'completedTasks' => Task::ownedBy($user)->notArchived()->completed()->count(),
                'overdueTasks' => Task::ownedBy($user)->notArchived()->open()->whereDate('due_date', '<', today())->count(),
            ],
            'statusCounts' => collect(TaskStatus::cases())
                ->mapWithKeys(fn (TaskStatus $status): array => [$status->value => (int) $statusCounts->get($status->value, 0)]),
            'workload' => [[
                'id' => $user->id,
                'name' => $user->name,
                'tasks' => (int) $assignedStatusCounts->sum(),
                'statusCounts' => collect(TaskStatus::cases())
                    ->reject(fn (TaskStatus $status): bool => $status === TaskStatus::Done)
                    ->mapWithKeys(fn (TaskStatus $status): array => [$status->value => (int) $assignedStatusCounts->get($status->value, 0)]),
            ]],
        ]);
    }
}
