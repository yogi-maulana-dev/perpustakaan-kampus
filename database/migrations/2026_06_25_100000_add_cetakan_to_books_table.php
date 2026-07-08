<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Cetakan / Edisi (kolom "Cet/ED" pada data perpustakaan).
            $table->string('cetakan', 50)->nullable()->after('tahun_terbit');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('cetakan');
        });
    }
};
