<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('paper_trades', function (Blueprint $table) {
            if (!Schema::hasColumn('paper_trades', 'pnl')) {
                $table->decimal('pnl', 20, 8)->nullable()->after('status');
            }
        });
    }
    public function down(): void
    {
        Schema::table('paper_trades', function (Blueprint $table) {
            $table->dropColumn('pnl');
        });
    }
};
