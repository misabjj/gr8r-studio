<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInboxController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workspace/Inbox');
    }
}
