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
                Schema::create('paper_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol');
            $table->enum('side', ['long','short']);
            $table->decimal('entry_price', 24, 8);
            $table->decimal('sl_price', 24, 8)->nullable();
            $table->decimal('tp_price', 24, 8)->nullable();
            $table->decimal('amount', 24, 8);
            $table->enum('status', ['open','closed'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paper_trades');
    }
};
