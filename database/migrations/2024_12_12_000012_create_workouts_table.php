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
        Schema::create('workouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('trainer_profiles')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->string('difficulty', 20);
            $table->integer('estimated_duration_minutes')->nullable();
            $table->string('pricing_type', 20)->default('free');
            $table->integer('price_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('draft');
            $table->integer('version')->default(1);
            $table->jsonb('tags')->nullable(); // array of tags
            $table->integer('total_exercises')->default(0);
            $table->integer('total_sets')->default(0);
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
        });

        // Set UUID default
        DB::statement('ALTER TABLE workouts ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraints for enums
        DB::statement("
            ALTER TABLE workouts ADD CONSTRAINT workouts_difficulty_check
            CHECK (difficulty IN ('beginner', 'intermediate', 'advanced', 'expert'))
        ");

        DB::statement("
            ALTER TABLE workouts ADD CONSTRAINT workouts_pricing_type_check
            CHECK (pricing_type IN ('free', 'premium'))
        ");

        DB::statement("
            ALTER TABLE workouts ADD CONSTRAINT workouts_status_check
            CHECK (status IN ('draft', 'published', 'archived'))
        ");

        // CHECK constraint: premium workouts must have price
        DB::statement("
            ALTER TABLE workouts ADD CONSTRAINT workouts_premium_requires_price_check
            CHECK (pricing_type != 'premium' OR price_cents IS NOT NULL)
        ");

        // Create indexes
        Schema::table('workouts', function (Blueprint $table) {
            $table->index('creator_id');
            $table->index('status');
            $table->index('pricing_type');
        });

        // GIN index for tags array search (PostgreSQL)
        DB::statement('CREATE INDEX workouts_tags_gin_idx ON workouts USING GIN (tags)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
