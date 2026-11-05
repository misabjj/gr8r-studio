<?php

namespace App\Actions\Projects;

use App\Models\Project;

class ToggleProjectFavorite
{
    public function handle(Project $project): Project
    {
        $project->is_favorite = ! $project->is_favorite;
        $project->save();

        return $project->refresh();
    }
}
