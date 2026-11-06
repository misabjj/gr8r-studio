<?php

namespace App\Actions\Workspace;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetWorkspaceTasks
{
    /** @return Collection<int, Task> */
    public function handle(User $owner): Collection
    {
        return Task::ownedBy($owner)
            ->notArchived()
            ->whereHas('project', fn (Builder $query) => $query->notArchived()->accessibleTo($owner))
            ->with(['project:id,name,key,color', 'assignee:id,name'])
            ->latest()
            ->get();
    }
}
