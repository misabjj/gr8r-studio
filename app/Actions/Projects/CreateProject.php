<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

class CreateProject
{
    public function handle(string $name, string $key, ?string $description, ?User $owner): Project
    {
        $project = new Project;
        $project->name = $name;
        $project->key = $key;
        $project->description = $description;
        $project->owner()->associate($owner);
        $project->save();

        return $project;
    }
}
