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
        Schema::create('intelligence_process_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('updated_orders')->default(0);
            $table->unsignedInteger('skipped_orders')->default(0);
            $table->unsignedInteger('failed_orders')->default(0);
            $table->enum('kitchen_status', ['normal', 'busy', 'overload'])->default('normal');
            $table->boolean('trend_sent')->default(false);
            $table->text('error_message')->nullable();
            $table->json('summary_payload')->nullable();
            $table->timestamps();

            $table->index('started_at');
            $table->index('kitchen_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_process_runs');
    }
};
