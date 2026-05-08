<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('mode');
            $table->unsignedTinyInteger('team_a_rounds')->nullable();
            $table->unsignedTinyInteger('team_b_rounds')->nullable();
            $table->timestamps();
            $table->index(['tournament_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
