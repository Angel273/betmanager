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
        Schema::create('bet_path_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bet_path_id')->constrained('bet_paths')->cascadeOnDelete();
            $table->integer('step_number');
            $table->decimal('calculated_odds', 10, 2);
            $table->decimal('expected_stake', 12, 2);
            $table->decimal('expected_payout', 12, 2);
            $table->foreignId('bet_id')->nullable()->constrained('bets')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, won, lost, voided
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_path_steps');
    }
};
