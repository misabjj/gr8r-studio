<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceMembersController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        return Inertia::render('Members/Index', [
            'workspaceData' => $getWorkspaceDemoData->handle($request->user()),
            'teams' => Team::query()->orderBy('name')->get(),
        ]);
    }
}
