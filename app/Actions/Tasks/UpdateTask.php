<?php

namespace App\Actions\Tasks;

use App\Data\Tasks\UpdateTaskData;
use App\Enums\TaskStatus;
use App\Models\Task;

class UpdateTask
{
    public function handle(Task $task, UpdateTaskData $data): Task
    {
        if ($data->updatesTitle) {
            $task->title = $data->title;
        }

        if ($data->updatesDescription) {
            $task->description = $data->description;
        }

        if ($data->updatesStatus && $data->status !== null) {
            $task->status = $data->status;
            $task->completed_at = $data->status === TaskStatus::Done ? now() : null;
        }

        if ($data->updatesPriority && $data->priority !== null) {
            $task->priority = $data->priority;
        }

        if ($data->updatesDueDate) {
            $task->due_date = $data->dueDate;
        }

        $task->save();

        return $task->refresh();
    }
}
