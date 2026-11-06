<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Actions\Workspace\GetWorkspaceTasks;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceTimelineController extends Controller
{
    public function index(Request $request, GetWorkspaceTasks $getWorkspaceTasks, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();

        return Inertia::render('Workspace/Timeline', [
            'tasks' => $getWorkspaceTasks->handle($user),
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->orderBy('name')
                ->get(['id', 'name', 'key', 'color']),
        ]);
    }
}
