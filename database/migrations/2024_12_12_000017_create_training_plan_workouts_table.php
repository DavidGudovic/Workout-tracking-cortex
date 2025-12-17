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
        Schema::create('training_plan_workouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_plan_day_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('workout_id')->constrained()->onDelete('restrict');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_optional')->default(false);
        });

        // Set UUID default
        DB::statement('ALTER TABLE training_plan_workouts ALTER COLUMN id SET DEFAULT gen_random_uuid()');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_plan_workouts');
    }
};
