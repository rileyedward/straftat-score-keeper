<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        DB::statement('CREATE UNIQUE INDEX tournaments_one_active ON tournaments(status) WHERE status = \'active\'');
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
