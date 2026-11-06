<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSearchController extends Controller
{
    public function index(Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $user = $request->user();
        $query = trim($request->string('q')->toString());
        $like = "%{$query}%";

        return Inertia::render('Workspace/Search', [
            'query' => $query,
            'workspaceData' => $getWorkspaceDemoData->handle($user),
            'projects' => $query === '' ? collect() : Project::ownedBy($user)
                ->notArchived()
                ->where(fn (Builder $builder) => $builder
                    ->whereRaw('LOWER(name) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(description) LIKE LOWER(?)', [$like]))
                ->withCount(['tasks' => fn (Builder $builder) => $builder->notArchived()])
                ->orderBy('name')
                ->get(),
            'tasks' => $query === '' ? collect() : Task::ownedBy($user)
                ->notArchived()
                ->whereHas('project', fn (Builder $builder) => $builder->notArchived())
                ->where(fn (Builder $builder) => $builder
                    ->whereRaw('LOWER(title) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(description) LIKE LOWER(?)', [$like]))
                ->with('project:id,name,key,color')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }
}
