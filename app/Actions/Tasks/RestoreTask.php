<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class RestoreTask
{
    public function handle(Task $task): Task
    {
        $task->is_archived = false;
        $task->archived_at = null;
        $task->save();

        return $task->refresh();
    }
}
