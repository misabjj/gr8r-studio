<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceTasks;
use App\Enums\ProjectStatus;
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
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->orderBy('name')
                ->get(['id', 'name', 'key', 'color']),
        ]);
    }
}
