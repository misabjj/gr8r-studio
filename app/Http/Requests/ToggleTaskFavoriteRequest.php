<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class ToggleTaskFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()?->id === $task->project->owner_id;
    }

    public function rules(): array
    {
        return [];
    }
}
