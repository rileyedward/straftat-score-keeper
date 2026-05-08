<?php

namespace App\Http\Controllers;

use App\Actions\RecordMatch;
use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function store(Request $request, Tournament $tournament, RecordMatch $action): RedirectResponse
    {
        abort_unless($tournament->status->value === 'active', 403);

        $payload = $this->validatePayload($request, $tournament);
        $action->create($tournament, $payload);

        return back()->with('success', 'Match recorded.');
    }

    public function update(Request $request, GameMatch $match, RecordMatch $action): RedirectResponse
    {
        abort_unless($match->tournament->status->value === 'active', 403);

        $payload = $this->validatePayload($request, $match->tournament);
        $action->update($match, $payload);

        return back()->with('success', 'Match updated.');
    }

    public function destroy(GameMatch $match): RedirectResponse
    {
        abort_unless($match->tournament->status->value === 'active', 403);

        $match->delete();

        return back()->with('success', 'Match removed.');
    }

    private function validatePayload(Request $request, Tournament $tournament): array
    {
        $rosterIds = $tournament->players()->pluck('players.id')->all();

        return $request->validate([
            'mode' => ['required', 'in:1v1,2v2,ffa'],
            'team_a_rounds' => ['nullable', 'integer', 'min:0', 'max:4'],
            'team_b_rounds' => ['nullable', 'integer', 'min:0', 'max:4'],
            'participants' => ['required', 'array'],
            'participants.*.player_id' => ['required', 'integer', 'in:'.implode(',', $rosterIds)],
            'participants.*.team' => ['nullable', 'in:A,B'],
            'participants.*.placement' => ['nullable', 'integer', 'min:1', 'max:4'],
            'participants.*.rounds_won' => ['nullable', 'integer', 'min:0', 'max:4'],
        ]);
    }
}
