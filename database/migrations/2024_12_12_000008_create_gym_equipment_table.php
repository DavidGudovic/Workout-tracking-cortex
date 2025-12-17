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
     * CRITICAL: Junction table linking gyms to preset equipment catalog
     * Used for workout-gym compatibility checking
     */
    public function up(): void
    {
        Schema::create('gym_equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gym_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('equipment_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable(); // e.g., "2nd floor", "members only"
            $table->timestamp('created_at')->useCurrent();

            // Unique constraint: prevent duplicate equipment in same gym
            $table->unique(['gym_id', 'equipment_id'], 'unique_gym_equipment');
        });

        // Set UUID default
        DB::statement('ALTER TABLE gym_equipment ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Create indexes for efficient lookup
        Schema::table('gym_equipment', function (Blueprint $table) {
            $table->index('gym_id');
            $table->index('equipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_equipment');
    }
};
