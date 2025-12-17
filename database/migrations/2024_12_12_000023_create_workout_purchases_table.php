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
        Schema::create('workout_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('workout_id')->constrained()->onDelete('restrict');
            $table->integer('workout_version');
            $table->integer('price_cents');
            $table->string('currency', 3);
            $table->string('payment_status', 20)->default('pending');
            $table->string('payment_reference', 255)->nullable(); // external payment ID
            $table->timestamp('purchased_at')->useCurrent();

            // Unique constraint: trainee can only purchase same workout once
            $table->unique(['trainee_id', 'workout_id'], 'unique_workout_purchase');
        });

        // Set UUID default
        DB::statement('ALTER TABLE workout_purchases ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for payment_status enum
        DB::statement("
            ALTER TABLE workout_purchases ADD CONSTRAINT workout_purchases_payment_status_check
            CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded'))
        ");

        // Create index on trainee_id for lookups
        Schema::table('workout_purchases', function (Blueprint $table) {
            $table->index('trainee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_purchases');
    }
};
