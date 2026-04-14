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
        Schema::create('intelligence_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_a_category_id')->unique();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('menu_count')->default(0);
            $table->timestamp('service_a_created_at')->nullable();
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->index('name');
            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_categories');
    }
};
