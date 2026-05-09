<?php

use App\Actions\RecordMatch;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->players = Player::factory()->count(4)->create();
    $this->tournament = Tournament::factory()->create();
    $this->tournament->players()->attach($this->players->pluck('id'));
    $this->action = new RecordMatch();
});

it('rejects 1v1 without exactly one player at 4 rounds', function () {
    $this->action->create($this->tournament, [
        'mode' => '1v1',
        'participants' => [
            ['player_id' => $this->players[0]->id, 'rounds_won' => 3],
            ['player_id' => $this->players[1]->id, 'rounds_won' => 2],
        ],
    ]);
})->throws(ValidationException::class);

it('accepts valid 1v1', function () {
    $match = $this->action->create($this->tournament, [
        'mode' => '1v1',
        'participants' => [
            ['player_id' => $this->players[0]->id, 'rounds_won' => 4],
            ['player_id' => $this->players[1]->id, 'rounds_won' => 1],
        ],
    ]);
    expect($match->participants)->toHaveCount(2);
});

it('rejects 2v2 if neither team has 4 rounds', function () {
    $this->action->create($this->tournament, [
        'mode' => '2v2',
        'team_a_rounds' => 3,
        'team_b_rounds' => 2,
        'participants' => [
            ['player_id' => $this->players[0]->id, 'team' => 'A'],
            ['player_id' => $this->players[1]->id, 'team' => 'A'],
            ['player_id' => $this->players[2]->id, 'team' => 'B'],
            ['player_id' => $this->players[3]->id, 'team' => 'B'],
        ],
    ]);
})->throws(ValidationException::class);

it('rejects 2v2 with both teams at 4 rounds', function () {
    $this->action->create($this->tournament, [
        'mode' => '2v2',
        'team_a_rounds' => 4,
        'team_b_rounds' => 4,
        'participants' => [
            ['player_id' => $this->players[0]->id, 'team' => 'A'],
            ['player_id' => $this->players[1]->id, 'team' => 'A'],
            ['player_id' => $this->players[2]->id, 'team' => 'B'],
            ['player_id' => $this->players[3]->id, 'team' => 'B'],
        ],
    ]);
})->throws(ValidationException::class);

it('accepts valid 2v2', function () {
    $match = $this->action->create($this->tournament, [
        'mode' => '2v2',
        'team_a_rounds' => 4,
        'team_b_rounds' => 2,
        'participants' => [
            ['player_id' => $this->players[0]->id, 'team' => 'A'],
            ['player_id' => $this->players[1]->id, 'team' => 'A'],
            ['player_id' => $this->players[2]->id, 'team' => 'B'],
            ['player_id' => $this->players[3]->id, 'team' => 'B'],
        ],
    ]);
    expect($match->team_a_rounds)->toBe(4);
});

it('rejects FFA without exactly one player at 4 rounds', function () {
    $this->action->create($this->tournament, [
        'mode' => 'ffa',
        'participants' => [
            ['player_id' => $this->players[0]->id, 'rounds_won' => 3],
            ['player_id' => $this->players[1]->id, 'rounds_won' => 3],
            ['player_id' => $this->players[2]->id, 'rounds_won' => 2],
        ],
    ]);
})->throws(ValidationException::class);

it('derives FFA placements from rounds_won (no ties)', function () {
    $match = $this->action->create($this->tournament, [
        'mode' => 'ffa',
        'participants' => [
            ['player_id' => $this->players[0]->id, 'rounds_won' => 1],
            ['player_id' => $this->players[1]->id, 'rounds_won' => 4],
            ['player_id' => $this->players[2]->id, 'rounds_won' => 2],
            ['player_id' => $this->players[3]->id, 'rounds_won' => 3],
        ],
    ]);

    $byPlayer = $match->participants->keyBy('player_id');
    expect($byPlayer[$this->players[1]->id]->placement)->toBe(1)
        ->and($byPlayer[$this->players[3]->id]->placement)->toBe(2)
        ->and($byPlayer[$this->players[2]->id]->placement)->toBe(3)
        ->and($byPlayer[$this->players[0]->id]->placement)->toBe(4);
});

it('derives FFA placements with tied middle places (ties fall down)', function () {
    $match = $this->action->create($this->tournament, [
        'mode' => 'ffa',
        'participants' => [
            ['player_id' => $this->players[0]->id, 'rounds_won' => 4],
            ['player_id' => $this->players[1]->id, 'rounds_won' => 3],
            ['player_id' => $this->players[2]->id, 'rounds_won' => 3],
            ['player_id' => $this->players[3]->id, 'rounds_won' => 1],
        ],
    ]);

    $byPlayer = $match->participants->keyBy('player_id');
    expect($byPlayer[$this->players[0]->id]->placement)->toBe(1)
        ->and($byPlayer[$this->players[1]->id]->placement)->toBe(3)
        ->and($byPlayer[$this->players[2]->id]->placement)->toBe(3)
        ->and($byPlayer[$this->players[3]->id]->placement)->toBe(4);
});

it('accepts valid FFA 3-player', function () {
    $match = $this->action->create($this->tournament, [
        'mode' => 'ffa',
        'participants' => [
            ['player_id' => $this->players[0]->id, 'rounds_won' => 4],
            ['player_id' => $this->players[1]->id, 'rounds_won' => 2],
            ['player_id' => $this->players[2]->id, 'rounds_won' => 1],
        ],
    ]);
    expect($match->participants)->toHaveCount(3);
});
