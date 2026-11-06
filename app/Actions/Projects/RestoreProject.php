<?php

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;

class RestoreProject
{
    public function handle(Project $project): Project
    {
        $project->status = ProjectStatus::tryFrom($project->archived_status ?? '') ?? ProjectStatus::Active;
        $project->archived_status = null;
        $project->archived_at = null;
        $project->save();

        return $project->refresh();
    }
}
