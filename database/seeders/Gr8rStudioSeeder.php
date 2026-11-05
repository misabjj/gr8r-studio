<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Gr8rStudioSeeder extends Seeder
{
    public function run(): void
    {
        $fixture = json_decode(
            file_get_contents(database_path('seeders/data/gr8r-studio.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $sourceData = $fixture['data'];
        $sourceGeneratedAt = CarbonImmutable::createFromTimestampMs($fixture['generatedAt']);
        $sourceToday = $sourceGeneratedAt->startOfDay();
        $this->seedTeams($fixture['teams']);
        $members = $this->seedMembers($sourceData['members'], $sourceGeneratedAt);
        $owner = $members[$sourceData['me']];
        $projects = $this->seedProjects($sourceData['projects'], $owner, $members, $sourceToday, $sourceGeneratedAt);

        $this->removeLegacySampleTasks(array_keys($projects));
        $this->seedTasks($sourceData['tasks'], $projects, $members, $sourceGeneratedAt, $sourceToday);

        DB::table('workspace_demo_snapshots')->updateOrInsert(
            ['name' => $sourceData['ws']['name']],
            [
                'source_data' => json_encode($sourceData, JSON_THROW_ON_ERROR),
                'source_generated_at' => $sourceGeneratedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /** @param array<int, array<string, string>> $sourceTeams */
    private function seedTeams(array $sourceTeams): void
    {
        foreach ($sourceTeams as $sourceTeam) {
            Team::query()->updateOrCreate(
                ['id' => $sourceTeam['id']],
                [
                    'name' => $sourceTeam['name'],
                    'icon' => $sourceTeam['icon'],
                    'color' => $sourceTeam['c'],
                    'description' => $sourceTeam['desc'],
                ],
            );
        }
    }

    /** @param array<int, string> $sourceProjectKeys */
    private function removeLegacySampleTasks(array $sourceProjectKeys): void
    {
        Task::query()
            ->whereNull('source_key')
            ->whereIn('title', [
                'Review the new navigation structure',
                'Finalize the homepage content',
                'Share responsive prototypes with the team',
                'Confirm launch checklist and owners',
                'Prepare the announcement draft',
                'Run final QA on the sign-up flow',
            ])
            ->whereHas('project', fn ($query) => $query->whereIn('source_key', $sourceProjectKeys))
            ->delete();
    }

    /** @param array<int, array<string, mixed>> $sourceMembers
     * @return array<string, User>
     */
    private function seedMembers(array $sourceMembers, CarbonImmutable $sourceGeneratedAt): array
    {
        $members = [];

        foreach ($sourceMembers as $sourceMember) {
            $member = User::query()->firstOrNew(['email' => $sourceMember['email']]);
            $member->fill([
                'source_key' => $sourceMember['id'],
                'name' => $sourceMember['name'],
                'role' => $sourceMember['role'],
                'team' => $sourceMember['team'],
                'title' => $sourceMember['title'],
                'color' => $sourceMember['c'],
                'status' => $sourceMember['status'],
                'last_active_at' => $sourceMember['last'] === null
                    ? null
                    : $sourceGeneratedAt->subMinutes($sourceMember['last']),
                'timezone' => $sourceMember['tz'],
            ]);

            if (! $member->exists || blank($member->password)) {
                $member->password = $sourceMember['id'] === 'm1' ? 'password' : Str::random(64);
            }

            $member->save();
            $members[$sourceMember['id']] = $member;
        }

        return $members;
    }

    /** @param array<int, array<string, mixed>> $sourceProjects
     * @param  array<string, User>  $members
     * @return array<string, Project>
     */
    private function seedProjects(
        array $sourceProjects,
        User $owner,
        array $members,
        CarbonImmutable $sourceToday,
        CarbonImmutable $sourceGeneratedAt,
    ): array {
        $projects = [];

        foreach ($sourceProjects as $sourceProject) {
            $project = Project::query()->where('source_key', $sourceProject['id'])->first()
                ?? Project::query()->where('key', $sourceProject['key'])->first()
                ?? Project::query()->where('name', $sourceProject['name'])->first()
                ?? new Project;

            $project->fill([
                'source_key' => $sourceProject['id'],
                'owner_id' => $owner->id,
                'name' => $sourceProject['name'],
                'key' => $sourceProject['key'],
                'description' => $sourceProject['desc'],
                'color' => $sourceProject['color'],
                'status' => $sourceProject['status'],
                'icon' => $sourceProject['icon'],
                'team' => $sourceProject['team'],
                'lead_id' => $members[$sourceProject['lead']]->id,
                'is_private' => $sourceProject['private'] ?? false,
                'is_favorite' => $sourceProject['fav'],
                'start_date' => $this->sourceDate($sourceProject['start'], $sourceToday),
                'due_date' => $this->sourceDate($sourceProject['due'], $sourceToday),
            ]);
            $project->save();
            DB::table('projects')->where('id', $project->id)->update([
                'updated_at' => $sourceGeneratedAt->subMinutes($sourceProject['last']),
            ]);
            $project->members()->sync(array_map(
                fn (string $sourceMemberId): int => $members[$sourceMemberId]->id,
                $sourceProject['members'],
            ));

            $projects[$sourceProject['id']] = $project;
        }

        return $projects;
    }

    /** @param array<int, array<string, mixed>> $sourceTasks
     * @param  array<string, Project>  $projects
     * @param  array<string, User>  $members
     */
    private function seedTasks(
        array $sourceTasks,
        array $projects,
        array $members,
        CarbonImmutable $sourceGeneratedAt,
        CarbonImmutable $sourceToday,
    ): void {
        foreach ($sourceTasks as $sourceTask) {
            $task = Task::query()->where('source_key', $sourceTask['id'])->first()
                ?? Task::query()->whereBelongsTo($projects[$sourceTask['project']])->where('title', $sourceTask['title'])->first()
                ?? new Task;

            $task->fill([
                'source_key' => $sourceTask['id'],
                'key' => $sourceTask['key'],
                'project_id' => $projects[$sourceTask['project']]->id,
                'assignee_id' => $members[$sourceTask['assignee']]->id,
                'title' => $sourceTask['title'],
                'description' => $sourceTask['desc'],
                'status' => $sourceTask['status'],
                'priority' => $sourceTask['priority'],
                'is_favorite' => $sourceTask['fav'],
                'start_date' => $this->sourceDate($sourceTask['start'], $sourceToday),
                'due_date' => $this->sourceDate($sourceTask['due'], $sourceToday),
                'completed_at' => $this->sourceTimestamp($sourceTask['completedAt'] ?? null, $sourceGeneratedAt),
                'labels' => $sourceTask['labels'],
                'subtasks' => $sourceTask['subtasks'],
                'attachments' => $sourceTask['attachments'],
                'dependencies' => $sourceTask['deps'],
                'estimate' => $sourceTask['estimate'],
                'sort_order' => $sourceTask['order'],
                'recurrence' => $sourceTask['recur'],
                'created_at' => $this->sourceTimestamp($sourceTask['created'], $sourceGeneratedAt),
                'updated_at' => $this->sourceTimestamp($sourceTask['updated'], $sourceGeneratedAt),
            ]);
            $task->save();
            DB::table('tasks')->where('id', $task->id)->update([
                'created_at' => $this->sourceTimestamp($sourceTask['created'], $sourceGeneratedAt),
                'updated_at' => $this->sourceTimestamp($sourceTask['updated'], $sourceGeneratedAt),
            ]);
        }
    }

    private function sourceDate(?string $sourceDate, CarbonImmutable $sourceToday): ?string
    {
        if ($sourceDate === null) {
            return null;
        }

        $offset = (int) $sourceToday->diffInDays(CarbonImmutable::parse($sourceDate)->startOfDay(), false);

        return today()->addDays($offset)->toDateString();
    }

    private function sourceTimestamp(?int $sourceTimestamp, CarbonImmutable $sourceGeneratedAt): ?CarbonImmutable
    {
        if ($sourceTimestamp === null) {
            return null;
        }

        $offsetMilliseconds = $sourceTimestamp - $sourceGeneratedAt->valueOf();

        return CarbonImmutable::now()->addMilliseconds($offsetMilliseconds);
    }
}
