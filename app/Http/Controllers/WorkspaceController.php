<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();

        return Inertia::render('Home/Index', [
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->notCompleted()
                ->with('members:id,name,color')
                ->withCount(['tasks' => fn (Builder $query) => $query->notArchived()])
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->notArchived()->completed()])
                ->orderBy('name')
                ->get(),
            'tasks' => Task::ownedBy($user)->notArchived()->with(['project:id,name,key,color', 'assignee:id,name,color'])->latest()->get(),
            'myTasks' => Task::ownedBy($user)->notArchived()->assignedTo($user)->with(['project:id,name,key,color', 'assignee:id,name,color'])->latest()->get(),
            'stats' => [
                'openTasks' => Task::ownedBy($user)->notArchived()->open()->count(),
                'completedTasks' => Task::ownedBy($user)->notArchived()->completed()->count(),
                'completedThisWeek' => Task::ownedBy($user)->notArchived()->completed()->where('completed_at', '>=', now()->startOfWeek())->count(),
                'projects' => Project::ownedBy($user)->count(),
            ],
            'workspaceData' => $getWorkspaceDemoData->handle($user),
        ]);
    }
}
