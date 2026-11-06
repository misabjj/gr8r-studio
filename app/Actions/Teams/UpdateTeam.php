<?php

namespace App\Actions\Teams;

use App\Enums\TeamColor;
use App\Enums\TeamIcon;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTeam
{
    /**
     * @param  array<int, int>  $memberIds
     */
    public function handle(
        Team $team,
        string $name,
        ?string $description,
        TeamIcon $icon,
        TeamColor $color,
        array $memberIds,
    ): Team {
        return DB::transaction(function () use ($team, $name, $description, $icon, $color, $memberIds): Team {
            $team->name = $name;
            $team->description = $description ?? '';
            $team->icon = $icon->value;
            $team->color = $color->hex();
            $team->save();

            User::query()->where('team', $team->id)->update(['team' => null]);

            User::query()->whereIn('id', $memberIds)->update(['team' => $team->id]);

            return $team->refresh();
        });
    }
}
