<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

class ResetUserPassword
{
    public function handle(string $token, string $email, string $password): string
    {
        return Password::reset(
            credentials: [
                'token' => $token,
                'email' => $email,
                'password' => $password,
            ],
            callback: static function (User $user) use ($password): void {
                $user->forceFill(['password' => $password])->save();
            },
        );
    }

    public function succeeded(string $status): bool
    {
        return $status === PasswordBroker::PASSWORD_RESET;
    }
}
