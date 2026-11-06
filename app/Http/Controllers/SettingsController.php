<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SettingsController extends Controller
{
    /** @return list<string> */
    public static function sections(): array
    {
        return [
            'workspace', 'appearance', 'language', 'datetime', 'members', 'teams',
            'projects', 'permissions', 'notif-email', 'notif-push', 'notif-mentions',
            'notif-assign', 'profile', 'preferences', 'shortcuts', 'password',
            'sessions', '2fa', 'plan', 'payment', 'invoices',
        ];
    }

    public function index(Request $request): HttpResponse
    {
        $section = $request->query('sec', 'appearance');
        abort_unless(is_string($section) && in_array($section, self::sections(), true), 404);

        $routeName = $section === '2fa' ? 'method2fa' : $section;

        return Inertia::location(route('settings.'.$routeName));
    }

    public function show(string $section): Response
    {
        abort_unless(in_array($section, self::sections(), true), 404);

        $page = match ($section) {
            '2fa' => 'TwoFactor',
            default => str($section)->studly()->toString(),
        };

        return Inertia::render('Settings/'.$page, [
            'section' => $section,
        ]);
    }
}
