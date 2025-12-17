<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('set_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exercise_log_id')->constrained()->onDelete('cascade');
            $table->integer('set_number');
            $table->integer('target_reps')->nullable();
            $table->integer('actual_reps')->nullable();
            $table->integer('target_duration_seconds')->nullable();
            $table->integer('actual_duration_seconds')->nullable();
            $table->integer('target_distance_meters')->nullable();
            $table->integer('actual_distance_meters')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->integer('rpe')->nullable(); // Rate of Perceived Exertion 1-10
            $table->boolean('is_warmup')->default(false);
            $table->boolean('is_failure')->default(false);
            $table->timestamp('completed_at')->nullable();

            // Unique constraint: set_number unique per exercise_log
            $table->unique(['exercise_log_id', 'set_number'], 'unique_set_number');
        });

        // Set UUID default
        DB::statement('ALTER TABLE set_logs ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // CHECK constraint for RPE
        DB::statement("
            ALTER TABLE set_logs ADD CONSTRAINT set_logs_rpe_check
            CHECK (rpe BETWEEN 1 AND 10)
        ");

        // Create index on exercise_log_id for lookups
        Schema::table('set_logs', function (Blueprint $table) {
            $table->index('exercise_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('set_logs');
    }
};
