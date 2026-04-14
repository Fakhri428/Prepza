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
        Schema::create('intelligence_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_a_menu_id')->unique();
            $table->foreignId('intelligence_category_id')->nullable()->constrained('intelligence_categories')->nullOnDelete();
            $table->unsignedBigInteger('service_a_category_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_external_url', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->index('name');
            $table->index('slug');
            $table->index('is_active');
            $table->index('service_a_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_menus');
    }
};
