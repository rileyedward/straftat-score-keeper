<?php

namespace App\Actions;

use App\Enums\MatchMode;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class ComputeTournamentLeaderboard
{
    public function __invoke(Tournament $tournament): Collection
    {
        $tournament->loadMissing([
            'players',
            'matches.participants.player',
        ]);

        $rows = $tournament->players->keyBy('id')->map(fn ($player) => [
            'player_id' => $player->id,
            'name' => $player->name,
            'image_url' => $player->image_url,
            'mp' => 0,
            'rp' => 0,
            'total' => 0,
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'mode_breakdown' => [
                '1v1' => ['mp' => 0, 'rp' => 0, 'wins' => 0, 'losses' => 0],
                '2v2' => ['mp' => 0, 'rp' => 0, 'wins' => 0, 'losses' => 0],
                'ffa' => ['mp' => 0, 'rp' => 0, 'wins' => 0, 'losses' => 0],
            ],
        ]);

        foreach ($tournament->matches as $match) {
            $mode = $match->mode;

            if ($mode === MatchMode::OneVOne) {
                $this->scoreOneVOne($match, $rows);
            } elseif ($mode === MatchMode::TwoVTwo) {
                $this->scoreTwoVTwo($match, $rows);
            } else {
                $this->scoreFfa($match, $rows);
            }
        }

        return $rows
            ->map(function ($row) {
                $row['total'] = $row['mp'] + $row['rp'];

                return $row;
            })
            ->values()
            ->sortByDesc(fn ($r) => [$r['total'], $r['mp'], $r['rp']])
            ->values();
    }

    private function scoreOneVOne($match, Collection $rows): void
    {
        $parts = $match->participants;
        if ($parts->count() !== 2) {
            return;
        }

        $sorted = $parts->sortByDesc('rounds_won')->values();
        $winner = $sorted[0];
        $loser = $sorted[1];

        $this->bump($rows, $winner->player_id, mp: 10, rp: $winner->rounds_won, mode: '1v1', win: true);
        $this->bump($rows, $loser->player_id, mp: 0, rp: $loser->rounds_won, mode: '1v1', win: false);
    }

    private function scoreTwoVTwo($match, Collection $rows): void
    {
        $teamA = $match->participants->where('team', 'A');
        $teamB = $match->participants->where('team', 'B');
        $aRounds = (int) ($match->team_a_rounds ?? 0);
        $bRounds = (int) ($match->team_b_rounds ?? 0);
        $aWins = $aRounds > $bRounds;

        foreach ($teamA as $p) {
            $this->bump($rows, $p->player_id, mp: $aWins ? 13 : 0, rp: $aRounds, mode: '2v2', win: $aWins);
        }
        foreach ($teamB as $p) {
            $this->bump($rows, $p->player_id, mp: $aWins ? 0 : 13, rp: $bRounds, mode: '2v2', win: ! $aWins);
        }
    }

    private function scoreFfa($match, Collection $rows): void
    {
        $count = $match->participants->count();
        $points = $count === 4
            ? [1 => 20, 2 => 15, 3 => 10, 4 => 5]
            : [1 => 15, 2 => 10, 3 => 5];

        foreach ($match->participants as $p) {
            $mp = $points[$p->placement] ?? 0;
            $win = $p->placement === 1;
            $this->bump($rows, $p->player_id, mp: $mp, rp: $p->rounds_won, mode: 'ffa', win: $win);
        }
    }

    private function bump(Collection $rows, int $playerId, int $mp, int $rp, string $mode, bool $win): void
    {
        if (! $rows->has($playerId)) {
            return;
        }

        $row = $rows[$playerId];
        $row['mp'] += $mp;
        $row['rp'] += $rp;
        $row['matches_played'] += 1;
        if ($win) {
            $row['wins'] += 1;
        } else {
            $row['losses'] += 1;
        }
        $row['mode_breakdown'][$mode]['mp'] += $mp;
        $row['mode_breakdown'][$mode]['rp'] += $rp;
        if ($win) {
            $row['mode_breakdown'][$mode]['wins'] += 1;
        } else {
            $row['mode_breakdown'][$mode]['losses'] += 1;
        }
        $rows[$playerId] = $row;
    }
}
