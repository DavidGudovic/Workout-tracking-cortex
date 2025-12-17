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
        Schema::create('training_plan_weeks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_plan_id')->constrained()->onDelete('cascade');
            $table->integer('week_number');
            $table->string('name', 100)->nullable(); // e.g., "Deload Week"
            $table->text('notes')->nullable();

            // Unique constraint: week_number unique per training plan
            $table->unique(['training_plan_id', 'week_number'], 'unique_week_number');
        });

        // Set UUID default
        DB::statement('ALTER TABLE training_plan_weeks ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Create index on training_plan_id for lookups
        Schema::table('training_plan_weeks', function (Blueprint $table) {
            $table->index('training_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_plan_weeks');
    }
};
