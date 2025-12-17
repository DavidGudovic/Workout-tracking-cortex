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
        Schema::create('exercise_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exercise_id')->constrained()->onDelete('cascade');
            $table->string('type', 20); // 'video_url', 'image_url', 'gif_url'
            $table->string('url', 1000);
            $table->string('title', 200)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        // Set UUID default
        DB::statement('ALTER TABLE exercise_media ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        // Add CHECK constraint for type enum
        DB::statement("
            ALTER TABLE exercise_media ADD CONSTRAINT exercise_media_type_check
            CHECK (type IN ('video_url', 'image_url', 'gif_url'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_media');
    }
};
