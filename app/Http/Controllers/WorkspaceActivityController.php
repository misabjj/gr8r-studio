<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceActivityController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        return Inertia::render('Workspace/Activity', [
            'workspaceData' => $getWorkspaceDemoData->handle($request->user()),
        ]);
    }
}
