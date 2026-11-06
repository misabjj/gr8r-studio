<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('create', [Task::class, $project]) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query): Builder => $query
                    ->whereNotNull('source_key')
                    ->orWhere('id', $this->route('project')?->owner_id)),
            ],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string', 'max:80'],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.id' => ['required', 'string', 'max:80'],
            'subtasks.*.title' => ['required', 'string', 'max:255'],
            'subtasks.*.done' => ['required', 'boolean'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.id' => ['required', 'string', 'max:80'],
            'attachments.*.name' => ['required', 'string', 'max:255'],
            'attachments.*.type' => ['required', 'string', 'max:30'],
            'attachments.*.size' => ['required', 'string', 'max:40'],
            'attachments.*.by' => ['required', 'integer', 'exists:users,id'],
            'attachments.*.at' => ['required', 'date'],
            'recurrence' => ['nullable', 'string', 'max:80'],
        ];
    }
}
