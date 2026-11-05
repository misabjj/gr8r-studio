<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = trim($request->string('q')->toString());
        $like = "%{$query}%";

        return Inertia::render('Workspace/Search', [
            'query' => $query,
            'projects' => $query === '' ? collect() : Project::ownedBy($user)
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->where(fn (Builder $builder) => $builder
                    ->whereRaw('LOWER(name) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(description) LIKE LOWER(?)', [$like]))
                ->withCount('tasks')
                ->orderBy('name')
                ->get(),
            'tasks' => $query === '' ? collect() : Task::ownedBy($user)
                ->whereHas('project', fn (Builder $builder) => $builder->where('status', '!=', ProjectStatus::Archived->value))
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
