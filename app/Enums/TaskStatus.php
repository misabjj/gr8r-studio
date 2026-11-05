<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case Progress = 'progress';
    case Review = 'review';
    case Done = 'done';
}
