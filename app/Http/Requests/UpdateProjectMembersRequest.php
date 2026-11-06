<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('update', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'members' => ['required', 'array'],
            'members.*' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
