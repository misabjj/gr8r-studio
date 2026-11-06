<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class DuplicateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $user = $this->user();

        return $project instanceof Project
            && ($user?->can('update', $project) ?? false)
            && ($user?->can('create', Project::class) ?? false);
    }
}
