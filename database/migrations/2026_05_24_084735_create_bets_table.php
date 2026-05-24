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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('single'); // single, parlay
            $table->decimal('stake', 12, 2);
            $table->decimal('odds', 10, 2);
            $table->decimal('payout', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending, won, lost, voided, half_won, half_lost
            $table->foreignId('bet_path_id')->nullable()->constrained('bet_paths')->nullOnDelete();
            $table->integer('bet_path_step')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
