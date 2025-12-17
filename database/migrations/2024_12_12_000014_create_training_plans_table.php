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
        Schema::create('training_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('trainer_profiles')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->string('goal', 30)->nullable();
            $table->string('difficulty', 20);
            $table->integer('duration_weeks');
            $table->integer('days_per_week');
            $table->string('pricing_type', 20)->default('free');
            $table->integer('price_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
        });

        // Set UUID default
        DB::statement('ALTER TABLE training_plans ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraints for enums
        DB::statement("
            ALTER TABLE training_plans ADD CONSTRAINT training_plans_goal_check
            CHECK (goal IN ('strength', 'hypertrophy', 'endurance', 'weight_loss', 'general_fitness', 'sport_specific'))
        ");

        DB::statement("
            ALTER TABLE training_plans ADD CONSTRAINT training_plans_difficulty_check
            CHECK (difficulty IN ('beginner', 'intermediate', 'advanced', 'expert'))
        ");

        DB::statement("
            ALTER TABLE training_plans ADD CONSTRAINT training_plans_pricing_type_check
            CHECK (pricing_type IN ('free', 'premium'))
        ");

        DB::statement("
            ALTER TABLE training_plans ADD CONSTRAINT training_plans_status_check
            CHECK (status IN ('draft', 'published', 'archived'))
        ");

        // CHECK constraint: days_per_week must be between 1-7
        DB::statement("
            ALTER TABLE training_plans ADD CONSTRAINT training_plans_days_per_week_check
            CHECK (days_per_week BETWEEN 1 AND 7)
        ");

        // Create index on creator_id for lookups
        Schema::table('training_plans', function (Blueprint $table) {
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_plans');
    }
};
