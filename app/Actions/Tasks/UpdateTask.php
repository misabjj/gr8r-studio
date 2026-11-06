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

        if ($data->updatesProjectId && $data->projectId !== null) {
            $task->project_id = $data->projectId;
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

        if ($data->updatesAssignee) {
            $task->assignee_id = $data->assigneeId;
        }

        if ($data->updatesStartDate) {
            $task->start_date = $data->startDate;
        }

        if ($data->updatesLabels && $data->labels !== null) {
            $task->labels = $data->labels;
        }

        if ($data->updatesRecurrence) {
            $task->recurrence = $data->recurrence;
        }

        if ($data->updatesEstimate) {
            $task->estimate = $data->estimate;
        }

        if ($data->updatesDependencies && $data->dependencies !== null) {
            $task->dependencies = $data->dependencies;
        }

        if ($data->updatesSubtasks && $data->subtasks !== null) {
            $task->subtasks = $data->subtasks;
        }

        $task->save();

        return $task->refresh();
    }
}
