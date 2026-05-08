<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image_path'];

    protected $appends = ['image_url'];

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_player')->withTimestamps();
    }

    public function matchParticipants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk(config('filesystems.default'))->url($this->image_path);
    }
}
