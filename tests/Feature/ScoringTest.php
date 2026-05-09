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

it('scores 1v1 standard win as 3-0', function () {
    $a = Player::factory()->create();
    $b = Player::factory()->create();
    $t = buildTournamentWith([$a, $b]);
    recordOneVOne($t, $a, $b, 4, 1);

    $rows = (new ComputeTournamentLeaderboard())($t);
    $aRow = $rows->firstWhere('player_id', $a->id);
    $bRow = $rows->firstWhere('player_id', $b->id);

    expect($aRow['points'])->toBe(3)
        ->and($aRow['wins'])->toBe(1)
        ->and($bRow['points'])->toBe(0)
        ->and($bRow['losses'])->toBe(1);
});

it('scores 1v1 close win (4-3) as 2-1', function () {
    $a = Player::factory()->create();
    $b = Player::factory()->create();
    $t = buildTournamentWith([$a, $b]);
    recordOneVOne($t, $a, $b, 4, 3);

    $rows = (new ComputeTournamentLeaderboard())($t);
    $aRow = $rows->firstWhere('player_id', $a->id);
    $bRow = $rows->firstWhere('player_id', $b->id);

    expect($aRow['points'])->toBe(2)
        ->and($aRow['wins'])->toBe(1)
        ->and($bRow['points'])->toBe(1)
        ->and($bRow['losses'])->toBe(1);
});

it('scores 2v2 standard win with 3 to each winner, 0 to each loser', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);
    recordTwoVTwo($t, [$a, $b], [$c, $d], 4, 2);

    $rows = (new ComputeTournamentLeaderboard())($t);

    expect($rows->firstWhere('player_id', $a->id)['points'])->toBe(3)
        ->and($rows->firstWhere('player_id', $b->id)['points'])->toBe(3)
        ->and($rows->firstWhere('player_id', $c->id)['points'])->toBe(0)
        ->and($rows->firstWhere('player_id', $d->id)['points'])->toBe(0);
});

it('scores 2v2 close win (4-3) as 2 each winner, 1 each loser', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);
    recordTwoVTwo($t, [$a, $b], [$c, $d], 4, 3);

    $rows = (new ComputeTournamentLeaderboard())($t);

    expect($rows->firstWhere('player_id', $a->id)['points'])->toBe(2)
        ->and($rows->firstWhere('player_id', $b->id)['points'])->toBe(2)
        ->and($rows->firstWhere('player_id', $c->id)['points'])->toBe(1)
        ->and($rows->firstWhere('player_id', $d->id)['points'])->toBe(1);
});

it('scores 4-player FFA by placement (3/2/1/0)', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);
    recordFfa($t, [$a, $b, $c, $d], [4, 3, 2, 1]);

    $rows = (new ComputeTournamentLeaderboard())($t);
    expect($rows->firstWhere('player_id', $a->id)['points'])->toBe(3)
        ->and($rows->firstWhere('player_id', $b->id)['points'])->toBe(2)
        ->and($rows->firstWhere('player_id', $c->id)['points'])->toBe(1)
        ->and($rows->firstWhere('player_id', $d->id)['points'])->toBe(0);
});

it('scores 3-player FFA by placement (2/1/0)', function () {
    [$a, $b, $c] = Player::factory()->count(3)->create()->all();
    $t = buildTournamentWith([$a, $b, $c]);
    recordFfa($t, [$a, $b, $c], [4, 2, 1]);

    $rows = (new ComputeTournamentLeaderboard())($t);
    expect($rows->firstWhere('player_id', $a->id)['points'])->toBe(2)
        ->and($rows->firstWhere('player_id', $b->id)['points'])->toBe(1)
        ->and($rows->firstWhere('player_id', $c->id)['points'])->toBe(0);
});

it('scores FFA ties by falling to lower placement', function () {
    [$a, $b, $c, $d] = Player::factory()->count(4)->create()->all();
    $t = buildTournamentWith([$a, $b, $c, $d]);

    $match = GameMatch::factory()->ffa()->create(['tournament_id' => $t->id]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $a->id, 'placement' => 1, 'rounds_won' => 4]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $b->id, 'placement' => 3, 'rounds_won' => 2]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $c->id, 'placement' => 3, 'rounds_won' => 2]);
    MatchParticipant::factory()->create(['match_id' => $match->id, 'player_id' => $d->id, 'placement' => 4, 'rounds_won' => 0]);

    $rows = (new ComputeTournamentLeaderboard())($t);

    expect($rows->firstWhere('player_id', $a->id)['points'])->toBe(3)
        ->and($rows->firstWhere('player_id', $b->id)['points'])->toBe(1)
        ->and($rows->firstWhere('player_id', $c->id)['points'])->toBe(1)
        ->and($rows->firstWhere('player_id', $d->id)['points'])->toBe(0);
});

it('orders leaderboard by points desc', function () {
    [$a, $b] = Player::factory()->count(2)->create()->all();
    $t = buildTournamentWith([$a, $b]);
    recordOneVOne($t, $a, $b, 4, 0);

    $rows = (new ComputeTournamentLeaderboard())($t);
    expect($rows->first()['player_id'])->toBe($a->id);
});
