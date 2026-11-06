<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class ArchiveTask
{
    public function handle(Task $task): Task
    {
        $task->is_archived = true;
        $task->archived_at = now();
        $task->save();

        return $task->refresh();
    }
}
