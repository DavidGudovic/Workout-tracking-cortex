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
        Schema::create('trainee_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('display_name', 100);
            $table->string('avatar_url', 500)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('fitness_goal', 50)->nullable();
            $table->string('experience_level', 20)->nullable();
            $table->timestamps();

            // Unique constraint: one trainee profile per user
            $table->unique('user_id', 'unique_trainee_per_user');
        });

        // Set UUID default
        DB::statement('ALTER TABLE trainee_profiles ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for fitness_goal enum
        DB::statement("
            ALTER TABLE trainee_profiles ADD CONSTRAINT trainee_profiles_fitness_goal_check
            CHECK (fitness_goal IN ('strength', 'hypertrophy', 'endurance', 'weight_loss', 'general_fitness', 'sport_specific'))
        ");

        // Add CHECK constraint for experience_level enum
        DB::statement("
            ALTER TABLE trainee_profiles ADD CONSTRAINT trainee_profiles_experience_level_check
            CHECK (experience_level IN ('beginner', 'intermediate', 'advanced', 'expert'))
        ");

        // Create index on user_id for lookups
        Schema::table('trainee_profiles', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainee_profiles');
    }
};
