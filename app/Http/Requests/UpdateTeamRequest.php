<?php

namespace App\Http\Requests;

use App\Enums\TeamColor;
use App\Enums\TeamIcon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team !== null && ($this->user()?->can('update', $team) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('teams', 'name')->ignore($this->route('team'))],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', Rule::enum(TeamIcon::class)],
            'color' => ['required', Rule::enum(TeamColor::class)],
            'member_ids' => ['present', 'array'],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ];
    }
}
