<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class UpdateProjectMembers
{
    /** @param array<int, int> $memberIds */
    public function handle(Project $project, User $owner, array $memberIds): Collection
    {
        $project->members()->sync(array_values(array_unique([$owner->id, ...$memberIds])));

        return $project->refresh()->members()->orderBy('name')->get(['users.id', 'name', 'email', 'color']);
    }
}
