<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceTasks;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceCalendarController extends Controller
{
    public function index(Request $request, GetWorkspaceTasks $getWorkspaceTasks): Response
    {
        return Inertia::render('Workspace/Calendar', [
            'tasks' => $getWorkspaceTasks->handle($request->user()),
            'projects' => Project::ownedBy($request->user())
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->orderBy('name')
                ->get(['id', 'name', 'key', 'color']),
        ]);
    }
}
