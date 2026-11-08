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
                Schema::create('upstream_healths', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->enum('status', ['ok','degraded','down'])->default('ok');
            $table->integer('latency_ms')->default(0);
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upstream_healths');
    }
};
