<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\RestoreTask;
use App\Http\Requests\RestoreTaskRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class TaskRestoreController extends Controller
{
    public function __invoke(
        Task $task,
        RestoreTaskRequest $request,
        RestoreTask $restoreTask,
    ): JsonResponse {
        return response()->json($restoreTask->handle($task));
    }
}
