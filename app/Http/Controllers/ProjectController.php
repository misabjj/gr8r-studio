<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Projects/Index', [
            'projects' => Project::ownedBy($request->user())->withCount('tasks')->latest()->get(),
        ]);
    }

    public function store(StoreProjectRequest $request, CreateProject $createProject): RedirectResponse|JsonResponse
    {
        $project = $createProject->handle(
            name: $request->validated('name'),
            key: $request->validated('key'),
            description: $request->validated('description'),
            owner: $request->user(),
        );

        if ($request->expectsJson()) {
            return response()->json($project, 201);
        }

        return to_route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        abort_unless($project->owner_id === request()->user()->id, 404);

        return Inertia::render('Projects/Show', [
            'project' => $project->load(['tasks' => fn ($query) => $query->latest()]),
        ]);
    }
}
