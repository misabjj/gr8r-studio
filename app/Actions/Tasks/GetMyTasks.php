<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetMyTasks
{
    /** @return Collection<int, Task> */
    public function handle(User $user): Collection
    {
        return Task::ownedBy($user)
            ->assignedTo($user)
            ->whereHas('project', fn (Builder $query) => $query->notArchived())
            ->with(['project:id,name,key,color', 'assignee:id,name'])
            ->latest()
            ->get();
    }
}
