<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Actions\Workspace\GetWorkspaceTasks;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceCalendarController extends Controller
{
    public function index(Request $request, GetWorkspaceTasks $getWorkspaceTasks, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();

        return Inertia::render('Workspace/Calendar', [
            'tasks' => $getWorkspaceTasks->handle($user),
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->notCompleted()
                ->notComplete()
                ->orderByRaw('CASE WHEN source_key IS NULL THEN 1 ELSE 0 END')
                ->orderBy('source_key')
                ->orderBy('name')
                ->with('members:id')
                ->get(['id', 'name', 'key', 'color', 'status', 'is_private']),
        ]);
    }
}
