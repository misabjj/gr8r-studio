<?php

namespace App\Actions\Teams;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteTeam
{
    public function handle(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            User::query()->where('team', $team->id)->update(['team' => null]);
            Project::query()->where('team', $team->id)->update(['team' => null]);
            $team->delete();
        });
    }
}
