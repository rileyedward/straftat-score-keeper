<?php

namespace Database\Factories;

use App\Models\MatchParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchParticipant>
 */
class MatchParticipantFactory extends Factory
{
    protected $model = MatchParticipant::class;

    public function definition(): array
    {
        return [
            'team' => null,
            'placement' => null,
            'rounds_won' => 0,
        ];
    }
}
