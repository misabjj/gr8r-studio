<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:10', 'regex:/^(?=.*[0-9]|.*[^A-Za-z0-9]).{10,}$/', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Use at least 10 characters, including a number or symbol',
            'password.regex' => 'Use at least 10 characters, including a number or symbol',
        ];
    }
}
