<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('update', $task) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('owner_id', $this->user()?->id)),
            ],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query): Builder => $query
                    ->whereNotNull('source_key')
                    ->orWhere('id', $this->route('task')?->project?->owner_id)),
            ],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string', Rule::in(['design', 'frontend', 'backend', 'research', 'content', 'bug', 'qa', 'growth'])],
            'recurrence' => ['sometimes', 'nullable', 'string', 'max:80'],
            'estimate' => ['sometimes', 'nullable', Rule::in(['30m', '1h', '2h', '4h', '1d', '2d', '3d', '5d', '8d'])],
            'dependencies' => ['sometimes', 'array'],
            'dependencies.*' => [
                'required',
                'string',
                'max:80',
                Rule::exists('tasks', 'source_key')->where(fn (Builder $query): Builder => $query
                    ->where('project_id', $this->route('task')?->project_id)
                    ->where('id', '!=', $this->route('task')?->id)),
            ],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.id' => ['required', 'string', 'max:80'],
            'subtasks.*.title' => ['required', 'string', 'max:255'],
            'subtasks.*.done' => ['required', 'boolean'],
            'subtasks.*.due' => ['nullable', 'date'],
            'subtasks.*.assignee' => ['nullable', 'string', 'max:80'],
            'subtasks.*.note' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
