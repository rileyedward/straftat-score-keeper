<?php

namespace Database\Seeders;

use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $players = Player::factory()->count(6)->create();

        $tournament = Tournament::factory()->create(['name' => 'Demo Game Night']);
        $tournament->players()->attach($players->pluck('id'));

        $this->seedOneVOne($tournament, $players[0], $players[1], 4, 2);
        $this->seedOneVOne($tournament, $players[2], $players[3], 4, 3);
        $this->seedOneVOne($tournament, $players[0], $players[2], 4, 1);
        $this->seedOneVOne($tournament, $players[1], $players[3], 4, 0);

        $this->seedTwoVTwo($tournament, [$players[0], $players[1]], [$players[2], $players[3]], 4, 3);
        $this->seedTwoVTwo($tournament, [$players[4], $players[5]], [$players[0], $players[2]], 4, 2);

        $this->seedFfa($tournament, [$players[0], $players[1], $players[2], $players[3]], [4, 3, 2, 1]);
        $this->seedFfa($tournament, [$players[1], $players[3], $players[4], $players[5]], [3, 4, 0, 2]);
        $this->seedFfa($tournament, [$players[0], $players[2], $players[4]], [2, 4, 1]);
        $this->seedFfa($tournament, [$players[1], $players[2], $players[5]], [4, 3, 0]);
    }

    private function seedOneVOne(Tournament $t, Player $a, Player $b, int $aRounds, int $bRounds): void
    {
        $match = GameMatch::factory()->oneVOne()->create(['tournament_id' => $t->id]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'player_id' => $a->id,
            'rounds_won' => $aRounds,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'player_id' => $b->id,
            'rounds_won' => $bRounds,
        ]);
    }

    private function seedTwoVTwo(Tournament $t, array $teamA, array $teamB, int $aRounds, int $bRounds): void
    {
        $match = GameMatch::factory()->twoVTwo($aRounds, $bRounds)->create(['tournament_id' => $t->id]);

        foreach ($teamA as $player) {
            MatchParticipant::factory()->create([
                'match_id' => $match->id,
                'player_id' => $player->id,
                'team' => 'A',
            ]);
        }
        foreach ($teamB as $player) {
            MatchParticipant::factory()->create([
                'match_id' => $match->id,
                'player_id' => $player->id,
                'team' => 'B',
            ]);
        }
    }

    private function seedFfa(Tournament $t, array $players, array $rounds): void
    {
        $match = GameMatch::factory()->ffa()->create(['tournament_id' => $t->id]);

        $combined = collect($players)->map(fn ($p, $i) => [
            'player' => $p,
            'rounds' => $rounds[$i],
        ])->sortByDesc('rounds')->values();

        foreach ($combined as $idx => $row) {
            MatchParticipant::factory()->create([
                'match_id' => $match->id,
                'player_id' => $row['player']->id,
                'placement' => $idx + 1,
                'rounds_won' => $row['rounds'],
            ]);
        }
    }
}
