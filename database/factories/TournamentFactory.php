<?php

namespace Database\Factories;

use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'name' => 'Game Night '.$this->faker->dateTimeBetween('-1 month', 'now')->format('M j'),
            'status' => TournamentStatus::Active->value,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function ended(): self
    {
        return $this->state(fn () => [
            'status' => TournamentStatus::Ended->value,
            'ended_at' => now(),
        ]);
    }
}
