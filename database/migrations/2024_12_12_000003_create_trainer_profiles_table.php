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
        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('display_name', 100);
            $table->string('slug', 100)->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->json('specializations')->nullable(); // array of specializations
            $table->json('certifications')->nullable(); // array of certification objects
            $table->integer('years_experience')->nullable();
            $table->integer('hourly_rate_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_available_for_hire')->default(true);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            // Unique constraint: one trainer profile per user
            $table->unique('user_id', 'unique_trainer_per_user');
        });

        // Set UUID default
        DB::statement('ALTER TABLE trainer_profiles ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE trainer_profiles ADD CONSTRAINT trainer_profiles_status_check
            CHECK (status IN ('pending', 'active', 'suspended'))
        ");

        // Create index on user_id for lookups
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_profiles');
    }
};
