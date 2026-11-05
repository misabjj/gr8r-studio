<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleProjectFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user() !== null && $project?->owner_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }
}
