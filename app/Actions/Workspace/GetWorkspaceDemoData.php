<?php

namespace App\Actions\Workspace;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GetWorkspaceDemoData
{
    /**
     * @return array{
     *     activity: array<int, array<string, mixed>>,
     *     comments: array<int, array<string, mixed>>,
     *     files: array<int, array<string, mixed>>,
     *     members: array<int, array<string, mixed>>,
     *     notifs: array<int, array<string, mixed>>,
     *     recentSearches: array<int, string>,
     *     projects: array<int, array<string, mixed>>,
     *     tasks: array<int, array<string, mixed>>,
     *     me: string,
     *     ws: array<string, mixed>,
     *     events: array<int, array<string, mixed>>
     * }
     */
    public function handle(User $user): array
    {
        if (! $user->projects()->whereNotNull('source_key')->exists()) {
            return $this->emptyData();
        }

        $sourceData = DB::table('workspace_demo_snapshots')
            ->where('name', 'Gr8r Studio')
            ->value('source_data');

        if (! is_string($sourceData)) {
            return $this->emptyData();
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($sourceData, true, flags: JSON_THROW_ON_ERROR);
        $projectIds = Project::ownedBy($user)
            ->whereNotNull('source_key')
            ->pluck('id', 'source_key');
        $taskIds = Task::query()
            ->whereHas('project', fn (Builder $query): Builder => $query->whereBelongsTo($user, 'owner'))
            ->whereNotNull('source_key')
            ->pluck('id', 'source_key');

        $projects = array_map(
            fn (array $project): array => [...$project, 'database_id' => $projectIds->get($project['id'])],
            $data['projects'] ?? [],
        );
        $tasks = array_map(
            fn (array $task): array => [...$task, 'database_id' => $taskIds->get($task['id'])],
            $data['tasks'] ?? [],
        );

        return [
            'activity' => $data['activity'] ?? [],
            'comments' => $data['comments'] ?? [],
            'files' => $data['files'] ?? [],
            'members' => $data['members'] ?? [],
            'notifs' => $data['notifs'] ?? [],
            'recentSearches' => $data['recentSearches'] ?? [],
            'projects' => $projects,
            'tasks' => $tasks,
            'me' => $data['me'] ?? '',
            'ws' => $data['ws'] ?? [],
            'events' => $data['events'] ?? [],
        ];
    }

    /**
     * @return array{
     *     activity: array<int, array<string, mixed>>,
     *     comments: array<int, array<string, mixed>>,
     *     files: array<int, array<string, mixed>>,
     *     members: array<int, array<string, mixed>>,
     *     notifs: array<int, array<string, mixed>>,
     *     recentSearches: array<int, string>,
     *     projects: array<int, array<string, mixed>>,
     *     tasks: array<int, array<string, mixed>>,
     *     me: string,
     *     ws: array<string, mixed>,
     *     events: array<int, array<string, mixed>>
     * }
     */
    private function emptyData(): array
    {
        return [
            'activity' => [],
            'comments' => [],
            'files' => [],
            'members' => [],
            'notifs' => [],
            'recentSearches' => [],
            'projects' => [],
            'tasks' => [],
            'me' => '',
            'ws' => [],
            'events' => [],
        ];
    }
}
