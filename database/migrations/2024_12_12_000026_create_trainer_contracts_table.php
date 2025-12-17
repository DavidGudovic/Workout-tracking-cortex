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
        Schema::create('trainer_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainee_id')->constrained('trainee_profiles')->onDelete('cascade');
            $table->foreignUuid('trainer_id')->constrained('trainer_profiles')->onDelete('restrict');
            $table->foreignUuid('gym_id')->nullable()->constrained()->onDelete('set null');
            $table->string('contract_type', 20);
            $table->integer('total_sessions')->nullable(); // for session_based
            $table->integer('sessions_used')->default(0);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->integer('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('active');
            $table->string('payment_reference', 255)->nullable();
            $table->timestamps();
        });

        // Set UUID default
        DB::statement('ALTER TABLE trainer_contracts ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraints for enums
        DB::statement("
            ALTER TABLE trainer_contracts ADD CONSTRAINT trainer_contracts_contract_type_check
            CHECK (contract_type IN ('session_based', 'time_based'))
        ");

        DB::statement("
            ALTER TABLE trainer_contracts ADD CONSTRAINT trainer_contracts_status_check
            CHECK (status IN ('active', 'completed', 'cancelled', 'expired'))
        ");

        // Create indexes
        Schema::table('trainer_contracts', function (Blueprint $table) {
            $table->index('trainee_id');
            $table->index('trainer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_contracts');
    }
};
