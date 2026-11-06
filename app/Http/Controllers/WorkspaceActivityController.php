<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Actions\Workspace\GetWorkspaceTasks;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceActivityController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData, GetWorkspaceTasks $getWorkspaceTasks): Response
    {
        $user = $request->user();

        return Inertia::render('Workspace/Activity', [
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'tasks' => $getWorkspaceTasks->handle($user),
        ]);
    }
}
