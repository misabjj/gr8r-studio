<?php

namespace App\Actions\Auth;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink
{
    public function handle(string $email): bool
    {
        return Password::sendResetLink(['email' => $email]) === PasswordBroker::RESET_LINK_SENT;
    }
}
