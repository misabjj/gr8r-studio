<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegisterUser
{
    public function handle(string $name, string $email, string $password): User
    {
        return User::query()->create(attributes: [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }
}
