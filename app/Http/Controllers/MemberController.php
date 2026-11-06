<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function show(string $member, Request $request, GetWorkspaceDemoData $getWorkspaceDemoData): Response
    {
        $workspaceData = $getWorkspaceDemoData->handle($request->user());

        abort_unless(collect($workspaceData['members'])->contains('id', $member), 404);

        return Inertia::render('Members/Show', [
            'memberId' => $member,
            'teams' => Team::query()->orderBy('name')->get(),
            'workspaceData' => $workspaceData,
        ]);
    }
}
