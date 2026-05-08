<?php

use App\Actions\ComputeTournamentLeaderboard;
use App\Enums\MatchMode;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Player;
use App\Models\Tournament;

function buildTournamentWith(array $players): Tournament
{
    $tournament = Tournament::factory()->create();
    $tournament->players()->attach(collect($players)->pluck('id'));

    return $tournament;
}

function recordOneVOne(Tournament $t, Player $a, Player $b, int $aRounds, int $bRounds): GameMatch
{
    $match = GameMatch::factory()->oneVOne()->create(['tournament_id' => $t->id]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $a->id, 'rounds_won' => $aRounds]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $b->id, 'rounds_won' => $bRounds]);

    return $match;
}

function recordTwoVTwo(Tournament $t, array $teamA, array $teamB, int $aRounds, int $bRounds): GameMatch
{
    $match = GameMatch::factory()->twoVTwo($aRounds, $bRounds)->create(['tournament_id' => $t->id]);
    foreach ($teamA as $p) {
        MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $p->id, 'team' => 'A']);
    }
    foreach ($teamB as $p) {
        MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $p->id, 'team' => 'B']);
    }

    return $match;
}

function recordFfa(Tournament $t, array $orderedPlayers, array $rounds): GameMatch
{
    $match = GameMatch::factory()->ffa()->create(['tournament_id' => $t->id]);
    foreach ($orderedPlayers as $i => $p) {
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'player_id' => $p->id,
            'placement' => $i + 1,
            'rounds_won' => $rounds[$i],
        ]);
    }

    return $match;
}

it('scores 1v1 winner with mp + rp', function () {
    $a = Player::factory()->create();
    $b = Player::factory()->create();
    $t = buildTournamentWith([$a, $b]);
    recordOneVOne($t, $a, $b, 4, 3);

    $rows = (new ComputeTournamentLeaderboard())($t);
    $aRow = $rows->firstWhere('player_id', $a->id);
    $bRow = $rows->firstWhere('player_id', $b->id);

    expect($aRow['mp'])->toBe(10)
        ->and($aRow['rp'])->toBe(4)
        ->and($aRow['total'])->toBe(14)
        ->and($aRow['wins'])->toBe(1)
        ->and($bRow['mp'])->toBe(0)
        ->and($bRow['rp'])->toBe(3)
        ->and($bRow['total'])->toBe(3)
        ->and($bRow['losses'])->toBe(1);
});

it('scores 2v2 with team rounds applied to each teammate', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);
    recordTwoVTwo($t, [$a, $b], [$c, $d], 4, 2);

    $rows = (new ComputeTournamentLeaderboard())($t);

    $aRow = $rows->firstWhere('player_id', $a->id);
    $cRow = $rows->firstWhere('player_id', $c->id);

    expect($aRow['mp'])->toBe(13)
        ->and($aRow['rp'])->toBe(4)
        ->and($aRow['total'])->toBe(17)
        ->and($cRow['mp'])->toBe(0)
        ->and($cRow['rp'])->toBe(2)
        ->and($cRow['total'])->toBe(2);
});

it('scores 4-player FFA by placement', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);
    recordFfa($t, [$a, $b, $c, $d], [4, 3, 2, 1]);

    $rows = (new ComputeTournamentLeaderboard())($t);
    expect($rows->firstWhere('player_id', $a->id)['mp'])->toBe(20)
        ->and($rows->firstWhere('player_id', $b->id)['mp'])->toBe(15)
        ->and($rows->firstWhere('player_id', $c->id)['mp'])->toBe(10)
        ->and($rows->firstWhere('player_id', $d->id)['mp'])->toBe(5);
});

it('scores 3-player FFA by placement', function () {
    [$a, $b, $c] = Player::factory()->count(3)->create()->all();
    $t = buildTournamentWith([$a, $b, $c]);
    recordFfa($t, [$a, $b, $c], [4, 2, 1]);

    $rows = (new ComputeTournamentLeaderboard())($t);
    expect($rows->firstWhere('player_id', $a->id)['mp'])->toBe(15)
        ->and($rows->firstWhere('player_id', $b->id)['mp'])->toBe(10)
        ->and($rows->firstWhere('player_id', $c->id)['mp'])->toBe(5);
});

it('scores FFA ties with shared place points', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);

    $match = GameMatch::factory()->ffa()->create(['tournament_id' => $t->id]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $a->id, 'placement' => 1, 'rounds_won' => 4]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $b->id, 'placement' => 2, 'rounds_won' => 3]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $c->id, 'placement' => 2, 'rounds_won' => 3]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $d->id, 'placement' => 4, 'rounds_won' => 1]);

    $rows = (new ComputeTournamentLeaderboard())($t);

    expect($rows->firstWhere('player_id', $a->id)['mp'])->toBe(20)
        ->and($rows->firstWhere('player_id', $b->id)['mp'])->toBe(15)
        ->and($rows->firstWhere('player_id', $c->id)['mp'])->toBe(15)
        ->and($rows->firstWhere('player_id', $d->id)['mp'])->toBe(5);
});

it('orders leaderboard by total desc', function () {
    [$a, $b] = Player::factory()->count(2)->create()->all();
    $t = buildTournamentWith([$a, $b]);
    recordOneVOne($t, $a, $b, 4, 0);

    $rows = (new ComputeTournamentLeaderboard())($t);
    expect($rows->first()['player_id'])->toBe($a->id);
});
