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
        Schema::create('training_plan_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_plan_week_id')->constrained()->onDelete('cascade');
            $table->integer('day_number'); // 1-7
            $table->string('name', 100)->nullable(); // e.g., "Push Day", "Rest"
            $table->boolean('is_rest_day')->default(false);
            $table->text('notes')->nullable();

            // Unique constraint: day_number unique per week
            $table->unique(['training_plan_week_id', 'day_number'], 'unique_day_number');
        });

        // Set UUID default
        DB::statement('ALTER TABLE training_plan_days ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // CHECK constraint: day_number must be between 1-7
        DB::statement("
            ALTER TABLE training_plan_days ADD CONSTRAINT training_plan_days_day_number_check
            CHECK (day_number BETWEEN 1 AND 7)
        ");

        // Create index on training_plan_week_id for lookups
        Schema::table('training_plan_days', function (Blueprint $table) {
            $table->index('training_plan_week_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_plan_days');
    }
};
