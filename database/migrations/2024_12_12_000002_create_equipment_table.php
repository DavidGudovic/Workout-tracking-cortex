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
        Schema::create('equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('category', 30);
            $table->text('description')->nullable();
            $table->string('icon_url', 500)->nullable();
            $table->boolean('is_common')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        // Set UUID default
        DB::statement('ALTER TABLE equipment ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for category enum
        DB::statement("
            ALTER TABLE equipment ADD CONSTRAINT equipment_category_check
            CHECK (category IN ('free_weights', 'machines', 'cardio', 'bodyweight', 'accessories', 'cable', 'plyometric'))
        ");

        // Create index on category for filtering
        Schema::table('equipment', function (Blueprint $table) {
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
