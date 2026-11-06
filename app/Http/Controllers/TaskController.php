<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\ArchiveTask;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Actions\Tasks\ToggleTaskFavorite;
use App\Actions\Tasks\UpdateTask;
use App\Data\Tasks\UpdateTaskData;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function archive(Task $task, ArchiveTask $archiveTask): JsonResponse
    {
        $this->authorize('archive', $task);

        return response()->json($archiveTask->handle($task));
    }

    public function destroy(Task $task, DeleteTask $deleteTask): JsonResponse
    {
        $this->authorize('delete', $task);

        $deleteTask->handle($task);

        return response()->json(status: 204);
    }

    public function toggleFavorite(Task $task, ToggleTaskFavorite $toggleTaskFavorite): JsonResponse
    {
        $this->authorize('update', $task);

        return response()->json($toggleTaskFavorite->handle($task));
    }

    public function store(Project $project, StoreTaskRequest $request, CreateTask $createTask): RedirectResponse|JsonResponse
    {
        $task = $createTask->handle(
            project: $project,
            title: $request->validated('title'),
            description: $request->validated('description'),
            status: TaskStatus::from($request->validated('status', TaskStatus::Todo->value)),
            priority: TaskPriority::from($request->validated('priority', TaskPriority::None->value)),
            dueDate: $request->validated('due_date'),
            assigneeId: $request->validated('assignee_id'),
            startDate: $request->validated('start_date'),
            labels: $request->validated('labels', []),
            subtasks: $request->validated('subtasks', []),
            attachments: $request->validated('attachments', []),
            recurrence: $request->validated('recurrence'),
        );

        if ($request->expectsJson()) {
            return response()->json($task->load(['project:id,name,key,color', 'assignee:id,name']), 201);
        }

        return to_route('projects.show', $project);
    }

    public function update(Task $task, UpdateTaskRequest $request, UpdateTask $updateTask): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $updatedTask = $updateTask->handle(
            task: $task,
            data: new UpdateTaskData(
                updatesTitle: array_key_exists('title', $validated),
                title: $validated['title'] ?? null,
                updatesDescription: array_key_exists('description', $validated),
                description: $validated['description'] ?? null,
                updatesProjectId: array_key_exists('project_id', $validated),
                projectId: $validated['project_id'] ?? null,
                updatesStatus: array_key_exists('status', $validated),
                status: isset($validated['status']) ? TaskStatus::from($validated['status']) : null,
                updatesPriority: array_key_exists('priority', $validated),
                priority: isset($validated['priority']) ? TaskPriority::from($validated['priority']) : null,
                updatesDueDate: array_key_exists('due_date', $validated),
                dueDate: $validated['due_date'] ?? null,
                updatesAssignee: array_key_exists('assignee_id', $validated),
                assigneeId: $validated['assignee_id'] ?? null,
                updatesStartDate: array_key_exists('start_date', $validated),
                startDate: $validated['start_date'] ?? null,
                updatesLabels: array_key_exists('labels', $validated),
                labels: $validated['labels'] ?? null,
                updatesRecurrence: array_key_exists('recurrence', $validated),
                recurrence: $validated['recurrence'] ?? null,
                updatesEstimate: array_key_exists('estimate', $validated),
                estimate: $validated['estimate'] ?? null,
                updatesDependencies: array_key_exists('dependencies', $validated),
                dependencies: $validated['dependencies'] ?? null,
                updatesSubtasks: array_key_exists('subtasks', $validated),
                subtasks: $validated['subtasks'] ?? null,
            ),
        );

        if ($request->expectsJson()) {
            return response()->json($updatedTask->load(['project:id,name,key,color', 'assignee:id,name']));
        }

        return back();
    }
}
