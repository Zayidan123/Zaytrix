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
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->integer('response_time_ms')->default(0);
            $table->decimal('cost_estimate', 12, 8)->default(0);
            $table->json('context_used')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropColumn('response_time_ms');
            $table->dropColumn('cost_estimate');
            $table->dropColumn('context_used');
        });
    }
};