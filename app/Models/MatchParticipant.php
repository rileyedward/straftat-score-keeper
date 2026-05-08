<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchParticipant extends Model
{
    use HasFactory;

    protected $fillable = ['match_id', 'player_id', 'team', 'placement', 'rounds_won'];

    protected $casts = [
        'placement' => 'integer',
        'rounds_won' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
