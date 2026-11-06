<?php

namespace App\Actions\Workspace;

use App\Models\User;

class SetWorkspaceNotificationRead
{
    public function handle(User $user, string $notificationKey, bool $isRead): void
    {
        $user->notificationReadStates()->updateOrCreate(
            ['notification_key' => $notificationKey],
            ['is_read' => $isRead],
        );
    }
}
