<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceTasks;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskIndexController extends Controller
{
    public function index(Request $request, GetWorkspaceTasks $getWorkspaceTasks): Response
    {
        $user = $request->user();

        return Inertia::render('Tasks/Index', [
            'tasks' => $getWorkspaceTasks->handle($user),
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->orderBy('name')
                ->get(['id', 'name', 'key', 'color']),
        ]);
    }
}
