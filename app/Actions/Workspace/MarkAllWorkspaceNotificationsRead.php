<?php

namespace App\Actions\Workspace;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkAllWorkspaceNotificationsRead
{
    public function __construct(private readonly GetWorkspaceDemoData $getWorkspaceDemoData) {}

    public function handle(User $user): int
    {
        $timestamp = now();
        $readStates = array_map(
            fn (array $notification): array => [
                'user_id' => $user->id,
                'notification_key' => $notification['id'],
                'is_read' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $this->getWorkspaceDemoData->handle($user)['notifs'],
        );

        if ($readStates === []) {
            return 0;
        }

        return DB::table('notification_read_states')->upsert(
            $readStates,
            ['user_id', 'notification_key'],
            ['is_read', 'updated_at'],
        );
    }
}
