<?php

namespace App\Http\Controllers;

use App\Actions\Teams\CreateTeam;
use App\Actions\Teams\DeleteTeam;
use App\Actions\Teams\UpdateTeam;
use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Enums\TeamColor;
use App\Enums\TeamIcon;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function store(StoreTeamRequest $request, CreateTeam $createTeam): JsonResponse
    {
        $validated = $request->validated();
        $team = $createTeam->handle(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            icon: TeamIcon::from($validated['icon']),
            color: TeamColor::from($validated['color']),
            memberIds: $validated['member_ids'],
        );

        return response()->json($team, 201);
    }

    public function update(UpdateTeamRequest $request, Team $team, UpdateTeam $updateTeam): JsonResponse
    {
        $validated = $request->validated();
        $updatedTeam = $updateTeam->handle(
            team: $team,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            icon: TeamIcon::from($validated['icon']),
            color: TeamColor::from($validated['color']),
            memberIds: $validated['member_ids'],
        );

        return response()->json($updatedTeam);
    }

    public function destroy(Team $team, DeleteTeam $deleteTeam): JsonResponse
    {
        $this->authorize('delete', $team);

        $deleteTeam->handle(team: $team);

        return response()->json(status: 204);
    }

    public function show(Team $team, Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        return Inertia::render('Teams/Show', [
            'team' => $team,
            'workspaceData' => $getWorkspaceDemoData->handle($request->user()),
        ]);
    }
}
