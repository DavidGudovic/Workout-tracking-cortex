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
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('workout_id')->constrained()->onDelete('restrict');
            $table->integer('workout_version');
            $table->foreignUuid('training_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('training_plan_week_number')->nullable();
            $table->integer('training_plan_day_number')->nullable();
            $table->string('status', 20)->default('started');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_duration_seconds')->nullable();
            $table->decimal('total_volume_kg', 10, 2)->nullable();
            $table->text('notes')->nullable(); // trainee reflection
            $table->integer('rating')->nullable(); // 1-5
            $table->timestamp('created_at')->useCurrent();
        });

        // Set UUID default
        DB::statement('ALTER TABLE workout_sessions ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraints for enums
        DB::statement("
            ALTER TABLE workout_sessions ADD CONSTRAINT workout_sessions_status_check
            CHECK (status IN ('started', 'in_progress', 'completed', 'abandoned'))
        ");

        // CHECK constraint for rating
        DB::statement("
            ALTER TABLE workout_sessions ADD CONSTRAINT workout_sessions_rating_check
            CHECK (rating BETWEEN 1 AND 5)
        ");

        // Create indexes
        Schema::table('workout_sessions', function (Blueprint $table) {
            $table->index('trainee_id');
            $table->index('workout_id');
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sessions');
    }
};
