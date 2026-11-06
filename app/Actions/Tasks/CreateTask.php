<?php

namespace App\Actions\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;

class CreateTask
{
    public function handle(
        Project $project,
        string $title,
        ?string $description,
        TaskStatus $status,
        TaskPriority $priority,
        ?string $dueDate,
        ?int $assigneeId,
        ?string $startDate,
        array $labels,
        array $subtasks,
        array $attachments,
        ?string $recurrence,
    ): Task {
        $task = $project->tasks()->make();
        $task->title = $title;
        $task->description = $description;
        $task->status = $status;
        $task->priority = $priority;
        $task->due_date = $dueDate;
        $task->start_date = $startDate;
        $task->assignee_id = $assigneeId;
        $task->labels = $labels;
        $task->subtasks = $subtasks;
        $task->attachments = $attachments;
        $task->recurrence = $recurrence;
        $task->save();

        return $task;
    }
}
