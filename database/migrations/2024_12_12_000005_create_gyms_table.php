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
        Schema::create('gyms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        // Set UUID default
        DB::statement('ALTER TABLE gyms ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for status enum
        DB::statement("
            ALTER TABLE gyms ADD CONSTRAINT gyms_status_check
            CHECK (status IN ('pending', 'active', 'suspended', 'closed'))
        ");

        // Create index on owner_id to find all gyms owned by a user
        Schema::table('gyms', function (Blueprint $table) {
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
