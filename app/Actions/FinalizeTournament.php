<?php

namespace App\Actions;

use App\Enums\TournamentStatus;
use App\Models\Tournament;

class FinalizeTournament
{
    public function __invoke(Tournament $tournament): Tournament
    {
        if ($tournament->status === TournamentStatus::Ended) {
            return $tournament;
        }

        $tournament->update([
            'status' => TournamentStatus::Ended->value,
            'ended_at' => now(),
        ]);

        return $tournament;
    }
}
