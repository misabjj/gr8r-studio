<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'hello@gr8rstudio.com'],
            ['name' => 'Tanjim Islam', 'password' => 'password'],
        );

        $website = Project::query()->firstOrCreate(
            ['key' => 'WEB'],
            ['owner_id' => $owner->id, 'name' => 'Website Redesign', 'description' => 'A thoughtful refresh of the Gr8r Studio website.', 'color' => 'indigo'],
        );

        $launch = Project::query()->firstOrCreate(
            ['key' => 'LAUNCH'],
            ['owner_id' => $owner->id, 'name' => 'Product Launch', 'description' => 'Prepare the next product release with a clear plan.', 'color' => 'teal'],
        );

        $tasks = [
            ['project_id' => $website->id, 'title' => 'Review the new navigation structure', 'status' => TaskStatus::Progress, 'priority' => TaskPriority::High],
            ['project_id' => $website->id, 'title' => 'Finalize the homepage content', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Medium],
            ['project_id' => $website->id, 'title' => 'Share responsive prototypes with the team', 'status' => TaskStatus::Review, 'priority' => TaskPriority::Low],
            ['project_id' => $launch->id, 'title' => 'Confirm launch checklist and owners', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Urgent],
            ['project_id' => $launch->id, 'title' => 'Prepare the announcement draft', 'status' => TaskStatus::Backlog, 'priority' => TaskPriority::None],
            ['project_id' => $launch->id, 'title' => 'Run final QA on the sign-up flow', 'status' => TaskStatus::Done, 'priority' => TaskPriority::High],
        ];

        foreach ($tasks as $task) {
            Task::query()->firstOrCreate(
                ['project_id' => $task['project_id'], 'title' => $task['title']],
                ['status' => $task['status'], 'priority' => $task['priority']],
            );
        }
    }
}
