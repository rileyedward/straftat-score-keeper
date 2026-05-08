<?php

namespace App\Models;

use App\Enums\TournamentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'started_at', 'ended_at'];

    protected $casts = [
        'status' => TournamentStatus::class,
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_player')->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TournamentStatus::Active->value);
    }

    public function scopeEnded(Builder $query): Builder
    {
        return $query->where('status', TournamentStatus::Ended->value);
    }
}
