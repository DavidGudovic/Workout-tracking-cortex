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
        Schema::create('progress_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->date('snapshot_date');
            $table->integer('total_workouts_completed')->default(0);
            $table->integer('total_workouts_started')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('total_volume_kg', 12, 2)->default(0);
            $table->integer('total_duration_minutes')->default(0);
            $table->integer('total_reps')->default(0);
            $table->integer('active_training_plans')->default(0);
            $table->integer('current_streak_days')->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Unique constraint: one snapshot per trainee per day
            $table->unique(['trainee_id', 'snapshot_date'], 'unique_daily_snapshot');
        });

        // Set UUID default
        DB::statement('ALTER TABLE progress_snapshots ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Create indexes
        Schema::table('progress_snapshots', function (Blueprint $table) {
            $table->index('trainee_id');
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_snapshots');
    }
};
