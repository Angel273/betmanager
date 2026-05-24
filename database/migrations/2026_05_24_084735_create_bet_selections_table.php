<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bet_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bet_id')->constrained('bets')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->cascadeOnDelete();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('team_home_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->foreignId('team_away_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->string('market_name'); // e.g. Moneyline, Over/Under 2.5, Handicap
            $table->string('selection');   // e.g. Real Madrid, Over 2.5, Lakers -5.5
            $table->decimal('odds', 10, 2);
            $table->string('status')->default('pending'); // pending, won, lost, voided
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_selections');
    }
};
