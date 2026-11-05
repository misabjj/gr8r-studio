<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendPasswordResetLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePasswordResetLinkRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(StorePasswordResetLinkRequest $request, SendPasswordResetLink $sendPasswordResetLink): JsonResponse|RedirectResponse
    {
        $sendPasswordResetLink->handle(email: $request->validated('email'));
        $message = 'If an account exists for that email, reset instructions will be sent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
