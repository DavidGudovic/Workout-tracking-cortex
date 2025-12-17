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
        Schema::create('subscription_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gym_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('billing_period', 20);
            $table->json('benefits')->nullable(); // JSON array of benefits
            $table->integer('max_members')->nullable();
            $table->boolean('includes_trainer_access')->default(false);
            $table->string('status', 20)->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Set UUID default
        DB::statement('ALTER TABLE subscription_tiers ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for billing_period enum
        DB::statement("
            ALTER TABLE subscription_tiers ADD CONSTRAINT subscription_tiers_billing_period_check
            CHECK (billing_period IN ('monthly', 'quarterly', 'yearly'))
        ");

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE subscription_tiers ADD CONSTRAINT subscription_tiers_status_check
            CHECK (status IN ('active', 'inactive'))
        ");

        // Create index on gym_id for lookups
        Schema::table('subscription_tiers', function (Blueprint $table) {
            $table->index('gym_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_tiers');
    }
};
