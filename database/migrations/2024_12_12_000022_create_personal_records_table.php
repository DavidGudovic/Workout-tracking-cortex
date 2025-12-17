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
        Schema::create('personal_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('exercise_id')->constrained()->onDelete('cascade');
            $table->string('record_type', 20);
            $table->decimal('value', 10, 2);
            $table->decimal('weight_kg', 6, 2)->nullable(); // context for max_reps
            $table->integer('reps')->nullable(); // context for max_weight
            $table->timestamp('achieved_at');
            $table->foreignUuid('workout_session_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('previous_record_id')->nullable(); // Self-referencing FK added separately
            $table->timestamp('created_at')->useCurrent();
        });

        // Set UUID default
        DB::statement('ALTER TABLE personal_records ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for record_type enum
        DB::statement("
            ALTER TABLE personal_records ADD CONSTRAINT personal_records_record_type_check
            CHECK (record_type IN ('max_weight', 'max_reps', 'max_duration', 'max_volume', 'max_distance'))
        ");

        // Add self-referencing foreign key
        Schema::table('personal_records', function (Blueprint $table) {
            $table->foreign('previous_record_id')
                ->references('id')
                ->on('personal_records')
                ->onDelete('set null');
        });

        // Create indexes
        Schema::table('personal_records', function (Blueprint $table) {
            $table->index('trainee_id');
            $table->index('exercise_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
