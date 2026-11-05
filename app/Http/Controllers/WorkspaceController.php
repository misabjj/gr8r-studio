<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Home/Index', [
            'projects' => Project::active()->ownedBy($user)->withCount('tasks')->orderBy('name')->get(),
            'tasks' => Task::ownedBy($user)->open()->with('project:id,name,key,color')->latest()->limit(8)->get(),
            'stats' => [
                'openTasks' => Task::ownedBy($user)->where('status', '!=', 'done')->count(),
                'completedTasks' => Task::ownedBy($user)->where('status', 'done')->count(),
                'projects' => Project::ownedBy($user)->count(),
            ],
        ]);
    }
}
