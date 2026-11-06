<?php

namespace App\Http\Controllers;

use App\Actions\Projects\RestoreProject;
use App\Http\Requests\RestoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ProjectRestoreController extends Controller
{
    public function __invoke(
        Project $project,
        RestoreProjectRequest $request,
        RestoreProject $restoreProject,
    ): JsonResponse {
        return response()->json($restoreProject->handle($project));
    }
}
