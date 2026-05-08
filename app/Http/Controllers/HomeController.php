<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $tournamentClass = '\\App\\Models\\Tournament';

        $activeTournament = null;
        $pastCount = 0;

        if (class_exists($tournamentClass)) {
            $active = $tournamentClass::query()
                ->where('status', 'active')
                ->first();

            if ($active) {
                $activeTournament = ['id' => $active->id, 'name' => $active->name];
            }

            $pastCount = $tournamentClass::query()
                ->where('status', 'ended')
                ->count();
        }

        return Inertia::render('Home', [
            'activeTournament' => $activeTournament,
            'pastTournamentsCount' => $pastCount,
        ]);
    }
}
