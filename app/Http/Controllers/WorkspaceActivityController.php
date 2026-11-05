<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class WorkspaceActivityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workspace/Activity');
    }
}
