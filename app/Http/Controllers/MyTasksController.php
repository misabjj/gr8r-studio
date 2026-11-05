<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\GetMyTasks;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTasksController extends Controller
{
    public function index(Request $request, GetMyTasks $getMyTasks): Response
    {
        $user = $request->user();

        return Inertia::render('Tasks/MyTasks', [
            'tasks' => $getMyTasks->handle($user),
            'projects' => Project::ownedBy($user)
                ->notArchived()
                ->orderBy('name')
                ->get(['id', 'name', 'key', 'color']),
        ]);
    }
}
