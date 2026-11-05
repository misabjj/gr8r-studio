<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class ToggleTaskFavorite
{
    public function handle(Task $task): Task
    {
        $task->is_favorite = ! $task->is_favorite;
        $task->save();

        return $task->refresh();
    }
}
