<?php

namespace App\Enums;

enum ProjectTemplate: string
{
    case Blank = 'blank';
    case Product = 'product';
    case Website = 'web';
    case Marketing = 'mkt';
    case Design = 'design';
    case Software = 'software';
    case Personal = 'personal';

    /** @return list<string> */
    public function starterTasks(): array
    {
        return match ($this) {
            self::Blank => [],
            self::Product => ['Define problem statement', 'User research plan', 'Write PRD', 'Design exploration', 'Build MVP', 'Beta launch'],
            self::Website => ['Sitemap & information architecture', 'Wireframes', 'Visual design', 'Build page templates', 'Content migration', 'QA & launch'],
            self::Marketing => ['Campaign brief', 'Audience research', 'Creative assets', 'Channel plan', 'Launch campaign', 'Performance report'],
            self::Design => ['Discovery workshop', 'Moodboard', 'Concept directions', 'Refinement', 'Developer handoff'],
            self::Software => ['Technical spec', 'Set up repo & CI', 'Implement core API', 'Write tests', 'Code review', 'Deploy to staging'],
            self::Personal => ['Brain dump ideas', 'Pick top 3 priorities', 'Schedule focus time', 'Weekly review'],
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Blank => 'folder',
            self::Product => 'target',
            self::Website => 'globe',
            self::Marketing => 'megaphone',
            self::Design => 'palette',
            self::Software => 'code',
            self::Personal => 'heart',
        };
    }
}
