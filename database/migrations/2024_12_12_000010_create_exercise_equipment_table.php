<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL: Junction table linking exercises to equipment options
     * Foundation of workout-gym compatibility checking
     */
    public function up(): void
    {
        Schema::create('exercise_equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exercise_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('equipment_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // main equipment vs alternatives
            $table->string('notes', 255)->nullable(); // e.g., "Can substitute with resistance band"

            // Unique constraint: prevent duplicate equipment for same exercise
            $table->unique(['exercise_id', 'equipment_id'], 'unique_exercise_equipment');
        });

        // Set UUID default
        DB::statement('ALTER TABLE exercise_equipment ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Create indexes for efficient lookup
        Schema::table('exercise_equipment', function (Blueprint $table) {
            $table->index('exercise_id');
            $table->index('equipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_equipment');
    }
};
