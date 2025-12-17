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
        Schema::create('exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->nullable()->constrained('trainer_profiles')->onDelete('set null');
            $table->string('type', 20)->default('custom'); // 'system' or 'custom'
            $table->string('visibility', 20)->default('private'); // 'private' or 'public_pool'
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable(); // step-by-step technique
            $table->string('exercise_type', 20); // 'repetition', 'duration', 'distance'
            $table->string('difficulty', 20);
            $table->jsonb('primary_muscle_groups')->nullable(); // array of muscle groups
            $table->jsonb('secondary_muscle_groups')->nullable(); // array of muscle groups
            $table->boolean('is_compound')->default(false);
            $table->decimal('calories_per_minute', 5, 2)->nullable();
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
        });

        // Set UUID default
        DB::statement('ALTER TABLE exercises ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraints for enums
        DB::statement("
            ALTER TABLE exercises ADD CONSTRAINT exercises_type_check
            CHECK (type IN ('system', 'custom'))
        ");

        DB::statement("
            ALTER TABLE exercises ADD CONSTRAINT exercises_visibility_check
            CHECK (visibility IN ('private', 'public_pool'))
        ");

        DB::statement("
            ALTER TABLE exercises ADD CONSTRAINT exercises_exercise_type_check
            CHECK (exercise_type IN ('repetition', 'duration', 'distance'))
        ");

        DB::statement("
            ALTER TABLE exercises ADD CONSTRAINT exercises_difficulty_check
            CHECK (difficulty IN ('beginner', 'intermediate', 'advanced', 'expert'))
        ");

        // Create indexes
        Schema::table('exercises', function (Blueprint $table) {
            $table->index('creator_id');
            $table->index('type');
            $table->index('visibility');
        });

        // GIN index for muscle groups array search (PostgreSQL)
        DB::statement('CREATE INDEX exercises_primary_muscle_groups_gin_idx ON exercises USING GIN (primary_muscle_groups)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
