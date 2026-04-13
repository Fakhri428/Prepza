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
        Schema::create('intelligence_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_a_order_id')->unique();
            $table->string('order_code')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('status')->nullable();
            $table->string('external_status')->nullable();
            $table->text('external_note')->nullable();
            $table->timestamp('external_updated_at')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->unsignedInteger('queue_number')->nullable();
            $table->string('queue_status')->nullable();
            $table->timestamp('service_a_created_at')->nullable();
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->index('status');
            $table->index('external_status');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_orders');
    }
};
