<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceArchiveController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();

        return Inertia::render('Workspace/Archive', [
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'archivedTasks' => Task::ownedBy($user)
                ->where('is_archived', true)
                ->whereHas('project', fn (Builder $query): Builder => $query->notArchived()->accessibleTo($user))
                ->with(['project:id,name,key,color,status', 'assignee:id,name,color'])
                ->orderByDesc('archived_at')
                ->get(),
            'archivedProjects' => Project::ownedBy($user)
                ->where('status', ProjectStatus::Archived)
                ->withCount('tasks')
                ->orderByDesc('archived_at')
                ->get(),
        ]);
    }
}
