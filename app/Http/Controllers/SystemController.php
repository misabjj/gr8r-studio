<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function design(): Response
    {
        return Inertia::render('System/Index', ['page' => 'design']);
    }

    public function states(): Response
    {
        return Inertia::render('System/Index', ['page' => 'states']);
    }

    public function brokenLink(): Response
    {
        return Inertia::render('System/NotFound');
    }
}
