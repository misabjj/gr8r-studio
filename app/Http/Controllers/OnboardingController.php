<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Onboarding/Index');
    }
}
