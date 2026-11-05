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
    ): Task {
        $task = $project->tasks()->make();
        $task->title = $title;
        $task->description = $description;
        $task->status = $status;
        $task->priority = $priority;
        $task->due_date = $dueDate;
        $task->save();

        return $task;
    }
}
