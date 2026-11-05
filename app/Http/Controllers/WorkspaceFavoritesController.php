<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceFavoritesController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Workspace/Favorites', [
            'projects' => Project::ownedBy($request->user())
                ->favorited()
                ->withCount('tasks')
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->completed()])
                ->orderBy('name')
                ->get(),
            'tasks' => Task::ownedBy($request->user())
                ->favorited()
                ->with(['project:id,name,key,color', 'assignee:id,name'])
                ->orderBy('title')
                ->get(),
        ]);
    }
}
