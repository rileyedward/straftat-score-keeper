<?php

namespace Database\Factories;

use App\Enums\MatchMode;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        return [
            'mode' => MatchMode::OneVOne->value,
            'team_a_rounds' => null,
            'team_b_rounds' => null,
        ];
    }

    public function oneVOne(): self
    {
        return $this->state(fn () => ['mode' => MatchMode::OneVOne->value]);
    }

    public function twoVTwo(int $teamARounds = 4, int $teamBRounds = 2): self
    {
        return $this->state(fn () => [
            'mode' => MatchMode::TwoVTwo->value,
            'team_a_rounds' => $teamARounds,
            'team_b_rounds' => $teamBRounds,
        ]);
    }

    public function ffa(): self
    {
        return $this->state(fn () => ['mode' => MatchMode::Ffa->value]);
    }
}
