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
        Schema::create('training_plan_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('training_plan_id')->constrained()->onDelete('restrict');
            $table->integer('price_cents');
            $table->string('currency', 3);
            $table->string('payment_status', 20)->default('pending');
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('purchased_at')->useCurrent();

            // Unique constraint: trainee can only purchase same plan once
            $table->unique(['trainee_id', 'training_plan_id'], 'unique_plan_purchase');
        });

        // Set UUID default
        DB::statement('ALTER TABLE training_plan_purchases ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for payment_status enum
        DB::statement("
            ALTER TABLE training_plan_purchases ADD CONSTRAINT training_plan_purchases_payment_status_check
            CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded'))
        ");

        // Create index on trainee_id for lookups
        Schema::table('training_plan_purchases', function (Blueprint $table) {
            $table->index('trainee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_plan_purchases');
    }
};
