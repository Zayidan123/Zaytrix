<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('webauthn_credentials', 'name')) {
                $table->string('name')->after('credential_id')->default('Security Key');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'transports')) {
                $table->json('transports')->nullable()->after('counter');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('transports');
            }
        });
    }
    public function down(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->dropColumn(['name', 'transports', 'last_used_at']);
        });
    }
};
