<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            // Satu peminjaman = satu denda (dipakai updateOrCreate untuk denda berjalan).
            $table->unique('loan_id');
        });
    }

    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropUnique(['loan_id']);
        });
    }
};
