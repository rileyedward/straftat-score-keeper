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
            'points' => 0,
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'mode_breakdown' => [
                '1v1' => ['points' => 0, 'wins' => 0, 'losses' => 0],
                '2v2' => ['points' => 0, 'wins' => 0, 'losses' => 0],
                'ffa' => ['points' => 0, 'wins' => 0, 'losses' => 0],
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
            ->values()
            ->sortByDesc(fn ($r) => [$r['points'], $r['wins']])
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

        [$winPoints, $losePoints] = $this->headToHeadPoints((int) $winner->rounds_won, (int) $loser->rounds_won);

        $this->bump($rows, $winner->player_id, points: $winPoints, mode: '1v1', win: true);
        $this->bump($rows, $loser->player_id, points: $losePoints, mode: '1v1', win: false);
    }

    private function scoreTwoVTwo($match, Collection $rows): void
    {
        $teamA = $match->participants->where('team', 'A');
        $teamB = $match->participants->where('team', 'B');
        $aRounds = (int) ($match->team_a_rounds ?? 0);
        $bRounds = (int) ($match->team_b_rounds ?? 0);
        $aWins = $aRounds > $bRounds;

        [$winPoints, $losePoints] = $this->headToHeadPoints(max($aRounds, $bRounds), min($aRounds, $bRounds));

        foreach ($teamA as $p) {
            $this->bump($rows, $p->player_id, points: $aWins ? $winPoints : $losePoints, mode: '2v2', win: $aWins);
        }
        foreach ($teamB as $p) {
            $this->bump($rows, $p->player_id, points: $aWins ? $losePoints : $winPoints, mode: '2v2', win: ! $aWins);
        }
    }

    private function scoreFfa($match, Collection $rows): void
    {
        $count = $match->participants->count();

        foreach ($match->participants as $p) {
            $placement = (int) $p->placement;
            $points = max(0, $count - $placement);
            $win = $placement === 1;
            $this->bump($rows, $p->player_id, points: $points, mode: 'ffa', win: $win);
        }
    }

    /**
     * 4-3 close match: winner 2, loser 1. Otherwise 3 / 0.
     *
     * @return array{0: int, 1: int}
     */
    private function headToHeadPoints(int $winnerRounds, int $loserRounds): array
    {
        if ($winnerRounds === 4 && $loserRounds === 3) {
            return [2, 1];
        }

        return [3, 0];
    }

    private function bump(Collection $rows, int $playerId, int $points, string $mode, bool $win): void
    {
        if (! $rows->has($playerId)) {
            return;
        }

        $row = $rows[$playerId];
        $row['points'] += $points;
        $row['matches_played'] += 1;
        if ($win) {
            $row['wins'] += 1;
        } else {
            $row['losses'] += 1;
        }
        $row['mode_breakdown'][$mode]['points'] += $points;
        if ($win) {
            $row['mode_breakdown'][$mode]['wins'] += 1;
        } else {
            $row['mode_breakdown'][$mode]['losses'] += 1;
        }
        $rows[$playerId] = $row;
    }
}
