<?php

namespace App\Http\Controllers;

use App\Actions\ComputeAchievements;
use App\Actions\ComputeTournamentLeaderboard;
use App\Actions\FinalizeTournament;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(): Response
    {
        $tournaments = Tournament::query()
            ->ended()
            ->withCount('matches')
            ->orderByDesc('ended_at')
            ->paginate(20);

        return Inertia::render('Tournaments/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        if (Tournament::active()->exists()) {
            return redirect()->route('home');
        }

        return Inertia::render('Tournaments/Create', [
            'players' => Player::orderBy('name')->get(['id', 'name', 'image_path']),
        ]);
    }

    public function store(Request $request, ComputeTournamentLeaderboard $leaderboard): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'player_ids' => ['required', 'array', 'min:2'],
            'player_ids.*' => ['integer', 'exists:players,id'],
        ]);

        if (Tournament::active()->exists()) {
            return back()->withErrors(['name' => 'A tournament is already active. End it first.']);
        }

        $tournament = DB::transaction(function () use ($data) {
            $t = Tournament::create([
                'name' => $data['name'],
                'status' => TournamentStatus::Active->value,
                'started_at' => now(),
            ]);
            $t->players()->attach($data['player_ids']);

            return $t;
        });

        return redirect()->route('tournaments.show', $tournament);
    }

    public function show(Tournament $tournament, ComputeTournamentLeaderboard $leaderboard): Response
    {
        $tournament->load([
            'players' => fn ($q) => $q->orderBy('name'),
            'matches' => fn ($q) => $q->latest('id'),
            'matches.participants.player',
        ]);

        return Inertia::render('Tournaments/Show', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->value,
                'started_at' => $tournament->started_at?->toIso8601String(),
                'ended_at' => $tournament->ended_at?->toIso8601String(),
            ],
            'roster' => $tournament->players->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image_url' => $p->image_url,
            ])->values(),
            'matches' => $tournament->matches->map(fn ($m) => [
                'id' => $m->id,
                'mode' => $m->mode->value,
                'team_a_rounds' => $m->team_a_rounds,
                'team_b_rounds' => $m->team_b_rounds,
                'created_at' => $m->created_at?->toIso8601String(),
                'participants' => $m->participants->map(fn ($mp) => [
                    'id' => $mp->id,
                    'player_id' => $mp->player_id,
                    'player_name' => $mp->player->name,
                    'team' => $mp->team,
                    'placement' => $mp->placement,
                    'rounds_won' => $mp->rounds_won,
                ])->values(),
            ])->values(),
            'leaderboard' => $leaderboard($tournament),
        ]);
    }

    public function addPlayers(Tournament $tournament, Request $request): RedirectResponse
    {
        if ($tournament->status !== TournamentStatus::Active) {
            abort(403);
        }

        $data = $request->validate([
            'player_ids' => ['required', 'array', 'min:1'],
            'player_ids.*' => ['integer', 'exists:players,id'],
        ]);

        $tournament->players()->syncWithoutDetaching($data['player_ids']);

        return back();
    }

    public function end(Tournament $tournament, FinalizeTournament $finalize): RedirectResponse
    {
        $finalize($tournament);

        return redirect()->route('tournaments.results', $tournament);
    }

    public function results(
        Tournament $tournament,
        ComputeTournamentLeaderboard $leaderboard,
        ComputeAchievements $achievements,
    ): Response {
        $tournament->load([
            'players' => fn ($q) => $q->orderBy('name'),
            'matches.participants.player',
        ]);

        return Inertia::render('Tournaments/Results', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->value,
                'started_at' => $tournament->started_at?->toIso8601String(),
                'ended_at' => $tournament->ended_at?->toIso8601String(),
                'matches_count' => $tournament->matches->count(),
            ],
            'leaderboard' => $leaderboard($tournament),
            'achievements' => $achievements($tournament),
        ]);
    }
}
