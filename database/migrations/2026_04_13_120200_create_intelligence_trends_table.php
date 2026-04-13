<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intelligence_trends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_run_id')->nullable()->constrained('intelligence_process_runs')->nullOnDelete();
            $table->string('title');
            $table->string('image_url', 2048);
            $table->string('caption', 300)->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamp('source_timestamp')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('sent_to_service_a')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index('detected_at');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_trends');
    }
};
