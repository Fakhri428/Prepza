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
        Schema::create('intelligence_menu_aliases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_a_alias_id')->nullable()->unique();
            $table->foreignId('intelligence_menu_id')->constrained('intelligence_menus')->cascadeOnDelete();
            $table->string('alias');
            $table->string('normalized_alias')->nullable();
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->index('alias');
            $table->index('normalized_alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intelligence_menu_aliases');
    }
};
