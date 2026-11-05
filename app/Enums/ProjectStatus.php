<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Planning = 'planning';
    case Risk = 'risk';
    case Hold = 'hold';
    case Complete = 'complete';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';
}
