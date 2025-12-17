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
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workout_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('exercise_id')->constrained()->onDelete('restrict');
            $table->integer('sort_order');
            $table->integer('sets')->default(1);
            $table->integer('target_reps')->nullable();
            $table->integer('target_duration_seconds')->nullable();
            $table->integer('target_distance_meters')->nullable();
            $table->integer('rest_seconds')->default(60);
            $table->text('notes')->nullable(); // trainer tips
            $table->integer('superset_group')->nullable(); // for supersets
            $table->boolean('is_optional')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        // Set UUID default
        DB::statement('ALTER TABLE workout_exercises ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // CHECK constraint: at least one target must be set
        DB::statement("
            ALTER TABLE workout_exercises ADD CONSTRAINT workout_exercises_valid_target_check
            CHECK (
                target_reps IS NOT NULL OR
                target_duration_seconds IS NOT NULL OR
                target_distance_meters IS NOT NULL
            )
        ");

        // Create index on workout_id for lookups
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->index('workout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
