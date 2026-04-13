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
        Schema::table('intelligence_orders', function (Blueprint $table) {
            $table->string('gender', 30)->nullable()->after('customer_name');
            $table->index('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intelligence_orders', function (Blueprint $table) {
            $table->dropIndex(['gender']);
            $table->dropColumn('gender');
        });
    }
};
