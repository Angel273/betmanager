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
        Schema::create('bet_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('start_amount', 12, 2);
            $table->decimal('target_amount', 12, 2);
            $table->decimal('reinvestment_rate', 5, 2); // e.g. 100.00 for 100%, 80.00 for 80%
            $table->integer('current_step')->default(1);
            $table->integer('total_steps');
            $table->string('status')->default('active'); // active, completed, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_paths');
    }
};
