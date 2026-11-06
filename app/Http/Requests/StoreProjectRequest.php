<?php

namespace App\Http\Requests;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Project::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'key' => ['required', 'string', 'max:12', 'alpha_num', 'uppercase', Rule::unique('projects', 'key')],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'icon' => ['sometimes', Rule::enum(ProjectIcon::class)],
            'color' => ['sometimes', Rule::enum(ProjectColor::class)],
            'team' => ['sometimes', 'nullable', 'string', Rule::exists('teams', 'id')],
            'lead_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'template' => ['sometimes', Rule::enum(ProjectTemplate::class)],
        ];
    }
}
