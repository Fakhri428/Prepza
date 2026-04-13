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
        Schema::create('intelligence_order_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_run_id')->nullable()->constrained('intelligence_process_runs')->nullOnDelete();
            $table->unsignedBigInteger('service_a_order_id')->index();
            $table->string('order_code')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('order_status')->nullable();
            $table->string('external_status_before')->nullable();
            $table->string('external_status_after')->nullable();
            $table->enum('priority', ['high', 'medium', 'low']);
            $table->enum('kitchen_status', ['normal', 'busy', 'overload']);
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->decimal('average_score', 8, 2)->default(0);
            $table->text('reason');
            $table->boolean('updated_to_service_a')->default(false);
            $table->timestamp('analyzed_at');
            $table->timestamps();

            $table->index(['priority', 'kitchen_status']);
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_order_analyses');
    }
};
