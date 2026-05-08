<?php

namespace App\Models;

use App\Enums\MatchMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = ['tournament_id', 'mode', 'team_a_rounds', 'team_b_rounds'];

    protected $casts = [
        'mode' => MatchMode::class,
        'team_a_rounds' => 'integer',
        'team_b_rounds' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class, 'match_id');
    }
}
