<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->query('email', ''),
            'token' => $token,
        ]);
    }

    public function store(StorePasswordResetRequest $request, ResetUserPassword $resetUserPassword): RedirectResponse
    {
        $status = $resetUserPassword->handle(
            token: $request->validated('token'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        if (! $resetUserPassword->succeeded($status)) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return to_route('login')->with('status', __(Password::PASSWORD_RESET));
    }
}
