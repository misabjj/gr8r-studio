<?php

namespace App\Actions\Workspace;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
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
     *     savedViews: array<int, array<string, mixed>>,
     *     projects: array<int, array<string, mixed>>,
     *     tasks: array<int, array<string, mixed>>,
     *     me: string,
     *     ws: array<string, mixed>,
     *     events: array<int, array<string, mixed>>
     * }
     */
    public function handle(User $user, bool $includeCalendarEvents = true): array
    {
        if (! $user->projects()->whereNotNull('source_key')->exists()) {
            return $this->emptyData();
        }

        $snapshot = DB::table('workspace_demo_snapshots')
            ->where('name', 'Gr8r Studio')
            ->first();

        if (! $snapshot || ! is_string($snapshot->source_data) || ! is_string($snapshot->source_generated_at)) {
            return $this->emptyData();
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($snapshot->source_data, true, flags: JSON_THROW_ON_ERROR);
        $sourceGeneratedAt = CarbonImmutable::parse($snapshot->source_generated_at);
        $visibleTaskProjectIds = array_values(array_map(
            fn (array $project): string => $project['id'],
            array_filter(
                $data['projects'] ?? [],
                fn (array $project): bool => ! ($project['private'] ?? false)
                    || in_array($data['me'] ?? '', $project['members'] ?? [], true),
            ),
        ));
        $projectIds = Project::ownedBy($user)
            ->notArchived()
            ->whereNotNull('source_key')
            ->pluck('id', 'source_key');
        $taskIds = Task::query()
            ->whereHas('project', fn (Builder $query): Builder => $query->whereBelongsTo($user, 'owner'))
            ->notArchived()
            ->whereNotNull('source_key')
            ->pluck('id', 'source_key');
        $memberIds = User::query()
            ->whereNotNull('source_key')
            ->pluck('id', 'source_key');

        $projects = array_map(
            fn (array $project): array => [...$project, 'database_id' => $projectIds->get($project['id'])],
            array_values(array_filter(
                $data['projects'] ?? [],
                fn (array $project): bool => $projectIds->has($project['id']),
            )),
        );
        $members = array_map(
            fn (array $member): array => [...$member, 'database_id' => $memberIds->get($member['id'])],
            $data['members'] ?? [],
        );
        $tasks = array_values(array_filter(
            $data['tasks'] ?? [],
            fn (array $task): bool => in_array($task['project'], $visibleTaskProjectIds, true)
                && $projectIds->has($task['project'])
                && $taskIds->has($task['id']),
        ));
        $tasks = $this->rebaseTimestamps($tasks, ['created', 'updated', 'completedAt'], $sourceGeneratedAt);
        $tasks = array_map(function (array $task) use ($sourceGeneratedAt, $taskIds): array {
            $task['database_id'] = $taskIds->get($task['id']);
            $task['attachments'] = $this->rebaseTimestamps($task['attachments'] ?? [], ['at'], $sourceGeneratedAt);

            return $task;
        }, $tasks);
        $activity = $this->rebaseTimestamps($data['activity'] ?? [], ['at'], $sourceGeneratedAt);
        $comments = $this->rebaseTimestamps($data['comments'] ?? [], ['at'], $sourceGeneratedAt);
        $files = $this->rebaseTimestamps($data['files'] ?? [], ['at'], $sourceGeneratedAt);
        $notifications = $this->rebaseTimestamps($data['notifs'] ?? [], ['at'], $sourceGeneratedAt);
        $readStates = $user->notificationReadStates()->pluck('is_read', 'notification_key');
        $notifications = array_map(
            fn (array $notification): array => [
                ...$notification,
                'read' => $readStates->get($notification['id'], $notification['read']),
            ],
            $notifications,
        );

        return [
            'activity' => $activity,
            'comments' => $comments,
            'files' => $files,
            'members' => $members,
            'notifs' => $notifications,
            'recentSearches' => $data['recentSearches'] ?? [],
            'savedViews' => $data['savedViews'] ?? [],
            'projects' => $projects,
            'tasks' => $tasks,
            'me' => $data['me'] ?? '',
            'ws' => $data['ws'] ?? [],
            'events' => $includeCalendarEvents ? $data['events'] ?? [] : [],
        ];
    }

    /** @param array<int, array<string, mixed>> $records
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function rebaseTimestamps(array $records, array $fields, CarbonImmutable $sourceGeneratedAt): array
    {
        return array_map(function (array $record) use ($fields, $sourceGeneratedAt): array {
            foreach ($fields as $field) {
                if (! isset($record[$field]) || ! is_int($record[$field])) {
                    continue;
                }

                $record[$field] = CarbonImmutable::now()->valueOf()
                    + $record[$field]
                    - $sourceGeneratedAt->valueOf();
            }

            return $record;
        }, $records);
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
            'savedViews' => [],
            'projects' => [],
            'tasks' => [],
            'me' => '',
            'ws' => [],
            'events' => [],
        ];
    }
}
