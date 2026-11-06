<?php

namespace App\Http\Requests;

use App\Actions\Workspace\GetWorkspaceDemoData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SetWorkspaceNotificationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        $notificationKeys = array_column(app(GetWorkspaceDemoData::class)->handle($user)['notifs'], 'id');

        return in_array($this->route('notification'), $notificationKeys, true);
    }

    public function rules(): array
    {
        return [
            'read' => ['required', 'boolean'],
        ];
    }
}
