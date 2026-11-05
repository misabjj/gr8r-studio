<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

class CreateProject
{
    public function handle(
        string $name,
        string $key,
        ?string $description,
        ?User $owner,
        ?string $startDate = null,
        ?string $dueDate = null,
    ): Project
    {
        $project = new Project;
        $project->name = $name;
        $project->key = $key;
        $project->description = $description;
        $project->start_date = $startDate;
        $project->due_date = $dueDate;
        $project->owner()->associate($owner);
        $project->save();

        return $project;
    }
}
