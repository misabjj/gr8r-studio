<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceFavoritesController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        return Inertia::render('Workspace/Favorites', [
            'workspaceData' => $getWorkspaceDemoData->handle($request->user()),
            'projects' => Project::ownedBy($request->user())
                ->favorited()
                ->with('members:id,name,color')
                ->withCount(['tasks' => fn (Builder $query) => $query->notArchived()])
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->notArchived()->completed()])
                ->orderBy('name')
                ->get(),
            'tasks' => Task::ownedBy($request->user())
                ->notArchived()
                ->favorited()
                ->with(['project:id,name,key,color', 'assignee:id,name'])
                ->orderBy('title')
                ->get(),
        ]);
    }
}
