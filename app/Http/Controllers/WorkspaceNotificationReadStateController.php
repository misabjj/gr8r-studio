<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\MarkAllWorkspaceNotificationsRead;
use App\Actions\Workspace\SetWorkspaceNotificationRead;
use App\Http\Requests\MarkAllWorkspaceNotificationsReadRequest;
use App\Http\Requests\SetWorkspaceNotificationReadRequest;
use Illuminate\Http\JsonResponse;

class WorkspaceNotificationReadStateController extends Controller
{
    public function update(
        string $notification,
        SetWorkspaceNotificationReadRequest $request,
        SetWorkspaceNotificationRead $setWorkspaceNotificationRead,
    ): JsonResponse {
        $read = $request->validated('read');
        $setWorkspaceNotificationRead->handle(
            user: $request->user(),
            notificationKey: $notification,
            isRead: $read,
        );

        return response()->json(['read' => $read]);
    }

    public function markAllRead(
        MarkAllWorkspaceNotificationsReadRequest $request,
        MarkAllWorkspaceNotificationsRead $markAllWorkspaceNotificationsRead,
    ): JsonResponse {
        $count = $markAllWorkspaceNotificationsRead->handle($request->user());

        return response()->json(['count' => $count]);
    }
}
