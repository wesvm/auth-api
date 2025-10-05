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
        Schema::table('tokens', function (Blueprint $table) {
            $table->boolean('is_revoked')->default(false)->change();
            $table->boolean('is_expired')->default(false)->change();

            $table->index('token');
            $table->index(['user_id', 'is_revoked', 'is_expired', 'expires_at'], 'tokens_user_status_index');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropIndex(['token']);
            $table->dropIndex('tokens_user_status_index');
            $table->dropIndex(['expires_at']);
        });
    }
};
