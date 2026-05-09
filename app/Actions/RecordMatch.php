<?php

namespace App\Actions;

use App\Enums\MatchMode;
use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordMatch
{
    /**
     * @param  array{mode: string, participants: array<int, array{player_id: int, team?: ?string, placement?: ?int, rounds_won?: ?int}>, team_a_rounds?: ?int, team_b_rounds?: ?int}  $payload
     */
    public function create(Tournament $tournament, array $payload): GameMatch
    {
        $this->validate($payload);
        $payload = $this->derivePlacements($payload);

        return DB::transaction(function () use ($tournament, $payload) {
            $match = $tournament->matches()->create([
                'mode' => $payload['mode'],
                'team_a_rounds' => $payload['mode'] === MatchMode::TwoVTwo->value ? ($payload['team_a_rounds'] ?? 0) : null,
                'team_b_rounds' => $payload['mode'] === MatchMode::TwoVTwo->value ? ($payload['team_b_rounds'] ?? 0) : null,
            ]);

            foreach ($payload['participants'] as $p) {
                $match->participants()->create([
                    'player_id' => $p['player_id'],
                    'team' => $p['team'] ?? null,
                    'placement' => $p['placement'] ?? null,
                    'rounds_won' => $p['rounds_won'] ?? 0,
                ]);
            }

            return $match;
        });
    }

    public function update(GameMatch $match, array $payload): GameMatch
    {
        $this->validate($payload);
        $payload = $this->derivePlacements($payload);

        return DB::transaction(function () use ($match, $payload) {
            $match->update([
                'mode' => $payload['mode'],
                'team_a_rounds' => $payload['mode'] === MatchMode::TwoVTwo->value ? ($payload['team_a_rounds'] ?? 0) : null,
                'team_b_rounds' => $payload['mode'] === MatchMode::TwoVTwo->value ? ($payload['team_b_rounds'] ?? 0) : null,
            ]);
            $match->participants()->delete();
            foreach ($payload['participants'] as $p) {
                $match->participants()->create([
                    'player_id' => $p['player_id'],
                    'team' => $p['team'] ?? null,
                    'placement' => $p['placement'] ?? null,
                    'rounds_won' => $p['rounds_won'] ?? 0,
                ]);
            }

            return $match->fresh(['participants.player']);
        });
    }

    /**
     * Derive FFA placements from rounds_won using modified competition ranking (1334).
     * Tied players share the lower placement; the higher placement is skipped.
     */
    private function derivePlacements(array $payload): array
    {
        if ($payload['mode'] !== MatchMode::Ffa->value) {
            return $payload;
        }

        $allRounds = collect($payload['participants'])->map(fn ($p) => (int) ($p['rounds_won'] ?? 0));

        foreach ($payload['participants'] as $i => &$p) {
            $myRounds = (int) ($p['rounds_won'] ?? 0);
            $strictlyAbove = $allRounds->filter(fn ($r) => $r > $myRounds)->count();
            $tiedWith = $allRounds->filter(fn ($r) => $r === $myRounds)->count() - 1;
            $p['placement'] = $strictlyAbove + $tiedWith + 1;
        }

        return $payload;
    }

    private function validate(array $payload): void
    {
        $mode = MatchMode::from($payload['mode']);
        $participants = collect($payload['participants']);

        $playerIds = $participants->pluck('player_id');
        if ($playerIds->count() !== $playerIds->unique()->count()) {
            throw ValidationException::withMessages(['participants' => 'A player cannot appear twice in the same match.']);
        }

        if ($mode === MatchMode::OneVOne) {
            if ($participants->count() !== 2) {
                throw ValidationException::withMessages(['participants' => '1v1 requires exactly 2 players.']);
            }
            $maxRounds = $participants->max('rounds_won');
            if ((int) $maxRounds !== 4) {
                throw ValidationException::withMessages(['rounds_won' => 'Exactly one player must reach 4 rounds.']);
            }
            $fours = $participants->where('rounds_won', 4)->count();
            if ($fours !== 1) {
                throw ValidationException::withMessages(['rounds_won' => 'Exactly one player must reach 4 rounds.']);
            }
        } elseif ($mode === MatchMode::TwoVTwo) {
            if ($participants->count() !== 4) {
                throw ValidationException::withMessages(['participants' => '2v2 requires exactly 4 players.']);
            }
            $teamA = $participants->where('team', 'A');
            $teamB = $participants->where('team', 'B');
            if ($teamA->count() !== 2 || $teamB->count() !== 2) {
                throw ValidationException::withMessages(['participants' => 'Each team needs exactly 2 players.']);
            }
            $aRounds = (int) ($payload['team_a_rounds'] ?? 0);
            $bRounds = (int) ($payload['team_b_rounds'] ?? 0);
            if (($aRounds === 4) === ($bRounds === 4)) {
                throw ValidationException::withMessages(['team_a_rounds' => 'Exactly one team must reach 4 rounds.']);
            }
            if ($aRounds < 0 || $bRounds < 0 || $aRounds > 4 || $bRounds > 4) {
                throw ValidationException::withMessages(['team_a_rounds' => 'Rounds must be between 0 and 4.']);
            }
        } else {
            $count = $participants->count();
            if ($count < 3 || $count > 4) {
                throw ValidationException::withMessages(['participants' => 'FFA requires 3 or 4 players.']);
            }
            $rounds = $participants->pluck('rounds_won')->map(fn ($v) => (int) $v);
            if ($rounds->contains(fn ($v) => $v < 0 || $v > 4)) {
                throw ValidationException::withMessages(['rounds_won' => 'Rounds must be between 0 and 4.']);
            }
            $fours = $rounds->filter(fn ($v) => $v === 4)->count();
            if ($fours !== 1) {
                throw ValidationException::withMessages(['rounds_won' => 'Exactly one player must reach 4 rounds.']);
            }
        }
    }
}
