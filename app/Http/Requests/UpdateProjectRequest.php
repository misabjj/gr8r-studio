<?php

namespace App\Http\Requests;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('update', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'icon' => ['sometimes', Rule::enum(ProjectIcon::class)],
            'color' => ['sometimes', Rule::enum(ProjectColor::class)],
            'team' => ['sometimes', 'nullable', 'string', Rule::exists('teams', 'id')],
            'lead_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
