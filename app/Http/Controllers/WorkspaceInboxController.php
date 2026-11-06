<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Actions\Workspace\GetWorkspaceTasks;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInboxController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData, GetWorkspaceTasks $getWorkspaceTasks): Response
    {
        $user = $request->user();

        return Inertia::render('Workspace/Inbox', [
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'tasks' => $getWorkspaceTasks->handle($user),
        ]);
    }
}
