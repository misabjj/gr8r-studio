<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInboxController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        return Inertia::render('Workspace/Inbox', [
            'workspaceData' => $getWorkspaceDemoData->handle($request->user()),
        ]);
    }
}
