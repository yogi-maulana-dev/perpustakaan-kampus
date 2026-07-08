<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Denormalisasi identitas pelaku (tetap tercatat walau user dihapus).
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('user_role', 50)->nullable()->after('user_name');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['user_name', 'user_role', 'user_agent']);
        });
    }
};
