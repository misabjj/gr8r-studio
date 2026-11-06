<?php

namespace App\Enums;

enum TeamColor: string
{
    case Indigo = 'indigo';
    case Blue = 'blue';
    case Violet = 'violet';
    case Teal = 'teal';
    case Rose = 'rose';
    case Amber = 'amber';
    case Green = 'green';
    case Slate = 'slate';

    public function hex(): string
    {
        return match ($this) {
            self::Indigo => '#5A67D8',
            self::Blue => '#3B82C4',
            self::Violet => '#8662C9',
            self::Teal => '#23918A',
            self::Rose => '#C54B78',
            self::Amber => '#C48A1E',
            self::Green => '#3D8E5F',
            self::Slate => '#6B7280',
        };
    }
}
