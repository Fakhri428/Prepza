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
        Schema::create('intelligence_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intelligence_order_id')->constrained('intelligence_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('service_a_item_id')->nullable();
            $table->string('item_name');
            $table->string('note')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->index('intelligence_order_id');
            $table->index('service_a_item_id');
            $table->index('item_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_order_items');
    }
};
