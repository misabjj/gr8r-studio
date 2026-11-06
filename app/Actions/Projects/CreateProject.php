<?php

namespace App\Actions\Projects;

use App\Actions\Tasks\CreateTask;
use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;

class CreateProject
{
    public function __construct(private readonly CreateTask $createTask) {}

    public function handle(
        string $name,
        string $key,
        ?string $description,
        User $owner,
        ?string $startDate = null,
        ?string $dueDate = null,
        ProjectIcon $icon = ProjectIcon::Folder,
        ProjectColor $color = ProjectColor::Indigo,
        ?string $team = null,
        ?User $lead = null,
        ProjectTemplate $template = ProjectTemplate::Blank,
    ): Project {
        $project = new Project;
        $project->name = $name;
        $project->key = $key;
        $project->description = $description;
        $project->icon = $icon->value;
        $project->color = $color->value;
        $project->status = ProjectStatus::Planning;
        $project->team = $team;
        $project->lead()->associate($lead);
        $project->start_date = $startDate ?? now()->toDateString();
        $project->due_date = $dueDate;
        $project->owner()->associate($owner);
        $project->save();
        $project->members()->syncWithoutDetaching(array_values(array_filter([$owner->id, $lead?->id])));

        foreach ($template->starterTasks() as $index => $title) {
            $this->createTask->handle(
                project: $project,
                title: $title,
                description: null,
                status: $index === 0 ? TaskStatus::Todo : TaskStatus::Backlog,
                priority: $index === 0 ? TaskPriority::High : TaskPriority::Medium,
                dueDate: now()->addDays(5 + ($index * 5))->toDateString(),
                assigneeId: $index === 0 ? ($lead?->id ?? $owner->id) : null,
                startDate: now()->addDays($index * 5)->toDateString(),
                labels: [],
                subtasks: [],
                attachments: [],
                recurrence: null,
            );
        }

        return $project;
    }
}
