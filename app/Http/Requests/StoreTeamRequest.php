<?php

namespace App\Http\Requests;

use App\Enums\TeamColor;
use App\Enums\TeamIcon;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Team::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('teams', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', Rule::enum(TeamIcon::class)],
            'color' => ['required', Rule::enum(TeamColor::class)],
            'member_ids' => ['present', 'array'],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ];
    }
}
