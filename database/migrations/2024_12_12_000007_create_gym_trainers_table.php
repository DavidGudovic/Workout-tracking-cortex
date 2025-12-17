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
        Schema::create('gym_trainers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gym_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('trainer_id')->constrained('trainer_profiles')->onDelete('cascade');
            $table->string('status', 20)->default('pending');
            $table->string('role', 30)->default('staff_trainer');
            $table->integer('hourly_rate_cents')->nullable();
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->timestamps();

            // Unique constraint: trainer can only have one active employment per gym
            $table->unique(['gym_id', 'trainer_id'], 'unique_active_employment');
        });

        // Set UUID default
        DB::statement('ALTER TABLE gym_trainers ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE gym_trainers ADD CONSTRAINT gym_trainers_status_check
            CHECK (status IN ('pending', 'active', 'terminated'))
        ");

        // Add CHECK constraint for role enum
        DB::statement("
            ALTER TABLE gym_trainers ADD CONSTRAINT gym_trainers_role_check
            CHECK (role IN ('staff_trainer', 'head_trainer', 'contractor'))
        ");

        // Create indexes
        Schema::table('gym_trainers', function (Blueprint $table) {
            $table->index('gym_id');
            $table->index('trainer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_trainers');
    }
};
