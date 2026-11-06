<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Actions\Workspace\GetWorkspaceTasks;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskIndexController extends Controller
{
    public function index(Request $request, GetWorkspaceTasks $getWorkspaceTasks, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();

        return Inertia::render('Tasks/Index', [
            'tasks' => $getWorkspaceTasks->handle($user),
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->accessibleTo($user)
                ->orderBy('id')
                ->get(['id', 'name', 'key', 'color', 'status', 'is_private']),
            'members' => User::query()
                ->whereNotNull('source_key')
                ->orderBy('name')
                ->get(['id', 'name', 'source_key']),
            'defaultFilter' => in_array($request->query('filter'), ['open', 'done', 'overdue'], true)
                ? $request->query('filter')
                : 'all',
        ]);
    }
}
