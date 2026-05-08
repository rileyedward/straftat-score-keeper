<?php

namespace App\Actions;

use App\Enums\MatchMode;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class ComputeAchievements
{
    public function __construct(private ComputeTournamentLeaderboard $leaderboard)
    {
    }

    public function __invoke(Tournament $tournament): Collection
    {
        $tournament->loadMissing([
            'players',
            'matches.participants.player',
        ]);

        $leaderboard = ($this->leaderboard)($tournament);
        $awards = collect();

        $awards = $awards->merge($this->theSniper($tournament));
        $awards = $awards->merge($this->bracketBuster($tournament));
        $awards = $awards->merge($this->wingman($tournament));
        $awards = $awards->merge($this->loneWolf($tournament));
        $awards = $awards->merge($this->ironLung($tournament));
        $awards = $awards->merge($this->cellarDweller($tournament));
        $awards = $awards->merge($this->comebackKid($tournament));
        $awards = $awards->merge($this->mvp($leaderboard));

        return $awards->values();
    }

    private function theSniper(Tournament $t): Collection
    {
        $best = null;
        foreach ($t->matches as $m) {
            if ($m->mode !== MatchMode::OneVOne) {
                continue;
            }
            $sorted = $m->participants->sortByDesc('rounds_won')->values();
            if ($sorted->count() !== 2) {
                continue;
            }
            $diff = $sorted[0]->rounds_won - $sorted[1]->rounds_won;
            if ($best === null || $diff > $best['diff']) {
                $best = [
                    'diff' => $diff,
                    'winner' => $sorted[0],
                ];
            }
        }

        if ($best === null) {
            return collect();
        }

        return collect([$this->award(
            'the_sniper',
            'The Sniper',
            'Biggest 1v1 round differential',
            $best['winner']->player_id,
            $best['winner']->player->name,
            "{$best['winner']->rounds_won}-".($best['winner']->rounds_won - $best['diff']),
        )]);
    }

    private function bracketBuster(Tournament $t): Collection
    {
        return $this->topByCount($t, fn ($m) => $m->mode === MatchMode::OneVOne, function ($m) {
            $sorted = $m->participants->sortByDesc('rounds_won')->values();

            return $sorted->count() === 2 ? [$sorted[0]->player_id => $sorted[0]->player->name] : [];
        }, 'bracket_buster', 'Bracket Buster', 'Most 1v1 wins');
    }

    private function wingman(Tournament $t): Collection
    {
        return $this->topByCount($t, fn ($m) => $m->mode === MatchMode::TwoVTwo, function ($m) {
            $aRounds = (int) ($m->team_a_rounds ?? 0);
            $bRounds = (int) ($m->team_b_rounds ?? 0);
            $winningTeam = $aRounds > $bRounds ? 'A' : 'B';
            $winners = $m->participants->where('team', $winningTeam);
            $out = [];
            foreach ($winners as $p) {
                $out[$p->player_id] = $p->player->name;
            }

            return $out;
        }, 'wingman', 'Wingman', 'Most 2v2 wins');
    }

    private function loneWolf(Tournament $t): Collection
    {
        return $this->topByCount($t, fn ($m) => $m->mode === MatchMode::Ffa, function ($m) {
            $first = $m->participants->where('placement', 1)->first();

            return $first ? [$first->player_id => $first->player->name] : [];
        }, 'lone_wolf', 'Lone Wolf', 'Most FFA 1st-place finishes');
    }

    private function ironLung(Tournament $t): Collection
    {
        $totals = collect();
        foreach ($t->matches as $m) {
            if ($m->mode === MatchMode::TwoVTwo) {
                $aRounds = (int) ($m->team_a_rounds ?? 0);
                $bRounds = (int) ($m->team_b_rounds ?? 0);
                foreach ($m->participants as $p) {
                    $rounds = $p->team === 'A' ? $aRounds : $bRounds;
                    $totals[$p->player_id] = ($totals[$p->player_id] ?? ['name' => $p->player->name, 'count' => 0]);
                    $row = $totals[$p->player_id];
                    $row['count'] += $rounds;
                    $totals[$p->player_id] = $row;
                }
            } else {
                foreach ($m->participants as $p) {
                    $totals[$p->player_id] = ($totals[$p->player_id] ?? ['name' => $p->player->name, 'count' => 0]);
                    $row = $totals[$p->player_id];
                    $row['count'] += $p->rounds_won;
                    $totals[$p->player_id] = $row;
                }
            }
        }

        return $this->topRow($totals, 'iron_lung', 'Iron Lung', 'Most rounds won across all matches', fn ($c) => "{$c} rounds");
    }

    private function cellarDweller(Tournament $t): Collection
    {
        return $this->topByCount($t, fn ($m) => $m->mode === MatchMode::Ffa, function ($m) {
            $last = $m->participants->sortByDesc('placement')->first();

            return $last ? [$last->player_id => $last->player->name] : [];
        }, 'cellar_dweller', 'Cellar Dweller', 'Most FFA last-place finishes');
    }

    private function comebackKid(Tournament $t): Collection
    {
        $byPlayer = collect();
        $matches = $t->matches->sortBy('id')->values();

        foreach ($matches as $m) {
            $results = $this->matchResults($m);
            foreach ($results as $playerId => $info) {
                $row = $byPlayer[$playerId] ?? ['name' => $info['name'], 'last' => null, 'longest' => 0, 'current' => 0];
                if ($row['last'] === 'L' && $info['win']) {
                    $row['current'] += 1;
                    if ($row['current'] > $row['longest']) {
                        $row['longest'] = $row['current'];
                    }
                } elseif (! $info['win']) {
                    $row['current'] = 0;
                }
                $row['last'] = $info['win'] ? 'W' : 'L';
                $byPlayer[$playerId] = $row;
            }
        }

        $top = $byPlayer->filter(fn ($r) => $r['longest'] > 0)
            ->sortByDesc('longest')->first();

        if (! $top || $top['longest'] === 0) {
            return collect();
        }

        $playerId = $byPlayer->search(fn ($r) => $r['longest'] === $top['longest']);

        return collect([$this->award('comeback_kid', 'Comeback Kid', 'Won immediately after losing', $playerId, $top['name'], "{$top['longest']} comeback".($top['longest'] === 1 ? '' : 's'))]);
    }

    private function mvp(Collection $leaderboard): Collection
    {
        if ($leaderboard->isEmpty()) {
            return collect();
        }
        $top = $leaderboard->first();

        return collect([$this->award('mvp', 'MVP', 'Highest total session score', $top['player_id'], $top['name'], "{$top['total']} pts")]);
    }

    private function matchResults($m): array
    {
        $out = [];
        if ($m->mode === MatchMode::OneVOne) {
            $sorted = $m->participants->sortByDesc('rounds_won')->values();
            if ($sorted->count() !== 2) {
                return [];
            }
            $out[$sorted[0]->player_id] = ['name' => $sorted[0]->player->name, 'win' => true];
            $out[$sorted[1]->player_id] = ['name' => $sorted[1]->player->name, 'win' => false];
        } elseif ($m->mode === MatchMode::TwoVTwo) {
            $aWin = (int) ($m->team_a_rounds ?? 0) > (int) ($m->team_b_rounds ?? 0);
            foreach ($m->participants as $p) {
                $out[$p->player_id] = [
                    'name' => $p->player->name,
                    'win' => ($p->team === 'A' && $aWin) || ($p->team === 'B' && ! $aWin),
                ];
            }
        } else {
            foreach ($m->participants as $p) {
                $out[$p->player_id] = [
                    'name' => $p->player->name,
                    'win' => $p->placement === 1,
                ];
            }
        }

        return $out;
    }

    private function topByCount(Tournament $t, callable $filter, callable $extractor, string $key, string $name, string $desc): Collection
    {
        $counts = collect();
        foreach ($t->matches as $m) {
            if (! $filter($m)) {
                continue;
            }
            foreach ($extractor($m) as $playerId => $playerName) {
                $row = $counts[$playerId] ?? ['name' => $playerName, 'count' => 0];
                $row['count'] += 1;
                $counts[$playerId] = $row;
            }
        }

        return $this->topRow($counts, $key, $name, $desc, fn ($c) => "{$c} win".($c === 1 ? '' : 's'));
    }

    private function topRow(Collection $counts, string $key, string $name, string $desc, callable $detailFmt): Collection
    {
        $top = $counts->sortByDesc('count')->first();
        if (! $top || $top['count'] === 0) {
            return collect();
        }

        $playerId = $counts->search(fn ($r) => $r['count'] === $top['count'] && $r['name'] === $top['name']);

        return collect([$this->award($key, $name, $desc, $playerId, $top['name'], $detailFmt($top['count']))]);
    }

    private function award(string $key, string $name, string $desc, ?int $playerId, string $playerName, string $detail): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'description' => $desc,
            'player_id' => $playerId,
            'player_name' => $playerName,
            'detail' => $detail,
        ];
    }
}
