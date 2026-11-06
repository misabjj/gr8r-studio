<?php

namespace App\Actions\Projects;

use App\Actions\Tasks\CreateTask;
use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectTemplate;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DuplicateProject
{
    public function __construct(
        private readonly CreateProject $createProject,
        private readonly CreateTask $createTask,
    ) {}

    public function handle(Project $project, User $owner): Project
    {
        return DB::transaction(function () use ($project, $owner): Project {
            $copy = $this->createProject->handle(
                name: "{$project->name} copy",
                key: $this->projectKey("{$project->name} copy"),
                description: $project->description,
                owner: $owner,
                startDate: $project->start_date?->toDateString(),
                dueDate: $project->due_date?->toDateString(),
                icon: ProjectIcon::from($project->icon ?: ProjectIcon::Folder->value),
                color: ProjectColor::from($project->color ?: ProjectColor::Indigo->value),
                team: $project->team,
                lead: $project->lead,
                template: ProjectTemplate::Blank,
            );

            foreach ($project->tasks()->notArchived()->orderBy('sort_order')->get() as $task) {
                $copyTask = $this->createTask->handle(
                    project: $copy,
                    title: $task->title,
                    description: $task->description,
                    status: TaskStatus::Todo,
                    priority: $task->priority,
                    dueDate: $task->due_date?->toDateString(),
                    assigneeId: $task->assignee_id,
                    startDate: $task->start_date?->toDateString(),
                    labels: $task->labels ?? [],
                    subtasks: $task->subtasks ?? [],
                    attachments: $task->attachments ?? [],
                    recurrence: $task->recurrence,
                );
                $copyTask->dependencies = $task->dependencies ?? [];
                $copyTask->sort_order = $task->sort_order;
                $copyTask->save();
            }

            return $copy->refresh()->load(['lead:id,name,color', 'members:id,name,color']);
        });
    }

    private function projectKey(string $name): string
    {
        $key = collect(preg_split('/\s+/', trim($name)) ?: [])
            ->map(fn (string $word): string => substr($word, 0, 1))
            ->implode('');
        $key = strtoupper(preg_replace('/[^A-Z]/', '', $key) ?: '');

        if (strlen($key) < 2) {
            $key = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'PRJ', 0, 3));
        }

        $base = substr($key, 0, 3);
        $suffix = '';
        $candidate = $base;

        while (Project::query()->where('key', $candidate)->exists()) {
            $suffix .= 'X';
            $candidate = substr($base, 0, 12 - strlen($suffix)).$suffix;
        }

        return $candidate;
    }
}
