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
        Schema::create('exercise_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workout_session_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('workout_exercise_id')->constrained()->onDelete('restrict');
            $table->foreignUuid('exercise_id')->constrained()->onDelete('restrict');
            $table->string('status', 20)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Set UUID default
        DB::statement('ALTER TABLE exercise_logs ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE exercise_logs ADD CONSTRAINT exercise_logs_status_check
            CHECK (status IN ('pending', 'in_progress', 'completed', 'skipped'))
        ");

        // Create index on workout_session_id for lookups
        Schema::table('exercise_logs', function (Blueprint $table) {
            $table->index('workout_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
    }
};
