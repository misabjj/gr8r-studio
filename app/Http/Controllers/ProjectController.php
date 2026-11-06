<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\ToggleProjectFavorite;
use App\Actions\Projects\UpdateProject;
use App\Actions\Projects\UpdateProjectMembers;
use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\ToggleProjectFavoriteRequest;
use App\Http\Requests\UpdateProjectMembersRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        return Inertia::render('Projects/Index', [
            'projects' => Project::ownedBy($request->user())
                ->notArchived()
                ->with(['lead:id,name,color', 'members:id,name,color'])
                ->withCount(['tasks' => fn (Builder $query) => $query->notArchived()])
                ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query->notArchived()->completed()])
                ->latest()
                ->get(),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
            'members' => User::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->get(['id', 'name', 'color', 'status']),
        ]);
    }

    public function store(StoreProjectRequest $request, CreateProject $createProject): RedirectResponse|JsonResponse
    {
        $project = $createProject->handle(
            name: $request->validated('name'),
            key: $request->validated('key'),
            description: $request->validated('description'),
            owner: $request->user(),
            startDate: $request->validated('start_date'),
            dueDate: $request->validated('due_date'),
            icon: ProjectIcon::from($request->validated('icon', ProjectIcon::Folder->value)),
            color: ProjectColor::from($request->validated('color', ProjectColor::Indigo->value)),
            team: $request->validated('team'),
            lead: User::query()->find($request->validated('lead_id')) ?? $request->user(),
            template: ProjectTemplate::from($request->validated('template', ProjectTemplate::Blank->value)),
        );

        if ($request->expectsJson()) {
            return response()->json($project, 201);
        }

        return to_route('projects.show', $project);
    }

    public function update(Project $project, UpdateProjectRequest $request, UpdateProject $updateProject): JsonResponse
    {
        $updatedProject = $updateProject->handle(
            project: $project,
            name: $request->validated('name', $project->name),
            description: $request->validated('description', $project->description),
            status: ProjectStatus::from($request->validated('status', $project->status->value)),
            icon: ProjectIcon::from($request->validated('icon', $project->icon ?: ProjectIcon::Folder->value)),
            color: ProjectColor::from($request->validated('color', $project->color ?: ProjectColor::Indigo->value)),
            team: $request->validated('team', $project->team),
            lead: $request->exists('lead_id') ? User::query()->find($request->validated('lead_id')) : $project->lead,
            startDate: $request->validated('start_date', $project->start_date?->toDateString()),
            dueDate: $request->validated('due_date', $project->due_date?->toDateString()),
        );

        return response()->json($updatedProject);
    }

    public function updateMembers(Project $project, UpdateProjectMembersRequest $request, UpdateProjectMembers $updateProjectMembers): JsonResponse
    {
        $members = $updateProjectMembers->handle(
            project: $project,
            owner: $request->user(),
            memberIds: $request->validated('members'),
        );

        return response()->json(['members' => $members]);
    }

    public function show(Project $project, GetWorkspaceDemoData $getWorkspaceDemoData, Request $request): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('Projects/Show', [
            'workspaceData' => $getWorkspaceDemoData->handle($request->user()),
            'projects' => Project::ownedBy($request->user())
                ->notArchived()
                ->orderBy('name')
                ->get(['id', 'name', 'key', 'color', 'status']),
            'project' => $project->load([
                'lead:id,name,color',
                'members:id,name,color',
                'tasks' => fn (HasMany $query) => $query->notArchived()->with('assignee:id,name')->orderBy('sort_order'),
            ]),
            'activeTab' => $request->query('tab', 'board'),
        ]);
    }

    public function toggleFavorite(Project $project, ToggleProjectFavoriteRequest $request, ToggleProjectFavorite $toggleProjectFavorite): JsonResponse
    {
        return response()->json($toggleProjectFavorite->handle($project));
    }
}
