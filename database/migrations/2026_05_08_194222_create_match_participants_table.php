<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->string('team', 1)->nullable();
            $table->unsignedTinyInteger('placement')->nullable();
            $table->unsignedTinyInteger('rounds_won')->default(0);
            $table->timestamps();
            $table->unique(['match_id', 'player_id']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_participants');
    }
};
