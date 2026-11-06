<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['Owner', 'Admin'], true);
    }

    public function update(User $user, Team $team): bool
    {
        return in_array($user->role, ['Owner', 'Admin'], true);
    }

    public function delete(User $user, Team $team): bool
    {
        return in_array($user->role, ['Owner', 'Admin'], true);
    }
}
