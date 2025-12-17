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
        Schema::create('gym_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('gym_id')->constrained()->onDelete('restrict');
            $table->foreignUuid('subscription_tier_id')->constrained()->onDelete('restrict');
            $table->string('status', 20)->default('active');
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamps();
        });

        // Set UUID default
        DB::statement('ALTER TABLE gym_subscriptions ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE gym_subscriptions ADD CONSTRAINT gym_subscriptions_status_check
            CHECK (status IN ('active', 'cancelled', 'expired', 'suspended'))
        ");

        // Create indexes
        Schema::table('gym_subscriptions', function (Blueprint $table) {
            $table->index('trainee_id');
            $table->index('gym_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_subscriptions');
    }
};
