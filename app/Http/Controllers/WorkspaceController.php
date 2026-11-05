<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Home/Index', [
            'projects' => Project::ownedBy($user)
                ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Archived->value])
                ->withCount('tasks')
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->completed()])
                ->orderBy('name')
                ->get(),
            'tasks' => Task::ownedBy($user)->with('project:id,name,key,color')->latest()->get(),
            'stats' => [
                'openTasks' => Task::ownedBy($user)->open()->count(),
                'completedTasks' => Task::ownedBy($user)->completed()->count(),
                'completedThisWeek' => Task::ownedBy($user)->completed()->where('completed_at', '>=', now()->startOfWeek())->count(),
                'projects' => Project::ownedBy($user)->count(),
            ],
        ]);
    }
}
