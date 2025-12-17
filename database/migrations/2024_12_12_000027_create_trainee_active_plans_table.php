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
        Schema::create('trainee_active_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('training_plan_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->useCurrent();
            $table->integer('current_week')->default(1);
            $table->integer('current_day')->default(1);
            $table->string('status', 20)->default('active');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Unique constraint: trainee can only have one active instance per plan
            $table->unique(['trainee_id', 'training_plan_id'], 'unique_active_plan');
        });

        // Set UUID default
        DB::statement('ALTER TABLE trainee_active_plans ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE trainee_active_plans ADD CONSTRAINT trainee_active_plans_status_check
            CHECK (status IN ('active', 'paused', 'completed', 'abandoned'))
        ");

        // Create indexes
        Schema::table('trainee_active_plans', function (Blueprint $table) {
            $table->index('trainee_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainee_active_plans');
    }
};
