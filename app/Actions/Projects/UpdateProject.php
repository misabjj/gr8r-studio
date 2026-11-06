<?php

namespace App\Actions\Projects;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class UpdateProject
{
    public function handle(
        Project $project,
        string $name,
        ?string $description,
        ProjectStatus $status,
        ProjectIcon $icon,
        ProjectColor $color,
        ?string $team,
        ?User $lead,
        ?string $startDate,
        ?string $dueDate,
    ): Project {
        if ($status === ProjectStatus::Archived && $project->status !== ProjectStatus::Archived) {
            $project->archived_status = $project->status->value;
            $project->archived_at = now();
        } elseif ($status !== ProjectStatus::Archived && $project->status === ProjectStatus::Archived) {
            $project->archived_status = null;
            $project->archived_at = null;
        }

        $project->name = $name;
        $project->description = $description;
        $project->status = $status;
        $project->icon = $icon->value;
        $project->color = $color->value;
        $project->team = $team;
        $project->lead()->associate($lead);
        $project->start_date = $startDate;
        $project->due_date = $dueDate;
        $project->save();

        return $project->refresh()->load(['lead:id,name,color', 'members:id,name,color']);
    }
}
