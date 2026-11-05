<?php

namespace App\Actions\Workspace;

use App\Enums\ProjectStatus;
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
            ->whereHas('project', fn (Builder $query) => $query->where('status', '!=', ProjectStatus::Archived->value))
            ->with('project:id,name,key,color')
            ->latest()
            ->get();
    }
}
