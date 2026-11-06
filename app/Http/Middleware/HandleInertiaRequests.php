<?php

namespace App\Http\Middleware;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'locale' => app()->getLocale(),
            'projects' => $request->user()
                ? Project::ownedBy($request->user())
                    ->whereNotIn('status', [ProjectStatus::Complete->value, ProjectStatus::Completed->value, ProjectStatus::Archived->value])
                    ->withCount('tasks')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'teams' => $request->user()
                ? Team::query()->orderBy('name')->get(['id', 'name', 'icon', 'color', 'description'])
                : collect(),
            'archiveCount' => $request->user()
                ? Project::ownedBy($request->user())->where('status', ProjectStatus::Archived)->count()
                    + Task::ownedBy($request->user())
                        ->where('is_archived', true)
                        ->whereHas('project', fn (Builder $query): Builder => $query->notArchived()->accessibleTo($request->user()))
                        ->count()
                : 0,
            'auth' => [
                'user' => $request->user(),
            ],
        ];
    }
}
