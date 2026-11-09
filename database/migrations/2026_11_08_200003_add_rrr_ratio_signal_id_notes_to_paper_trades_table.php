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
        Schema::table('paper_trades', function (Blueprint $table) {
            $table->decimal('rrr_ratio', 8, 4)->nullable();
            $table->foreignId('signal_id')->nullable()->constrained('strategies')->nullOnDelete();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_trades', function (Blueprint $table) {
            $table->dropForeign(['signal_id']);
            $table->dropColumn('rrr_ratio');
            $table->dropColumn('signal_id');
            $table->dropColumn('notes');
        });
    }
};