<?php

namespace App\Actions\Teams;

use App\Enums\TeamColor;
use App\Enums\TeamIcon;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTeam
{
    /**
     * @param  array<int, int>  $memberIds
     */
    public function handle(
        string $name,
        ?string $description,
        TeamIcon $icon,
        TeamColor $color,
        array $memberIds,
    ): Team {
        return DB::transaction(function () use ($name, $description, $icon, $color, $memberIds): Team {
            $team = Team::query()->create([
                'id' => 'tm_'.Str::lower(Str::random(12)),
                'name' => $name,
                'description' => $description ?? '',
                'icon' => $icon->value,
                'color' => $color->hex(),
            ]);

            User::query()->whereIn('id', $memberIds)->update(['team' => $team->id]);

            return $team;
        });
    }
}
