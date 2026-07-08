<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            // Null = dihitung otomatis dari tanggal daftar + masa berlaku (Pengaturan).
            $table->date('kartu_berlaku_sampai')->nullable()->after('foto');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->dropColumn('kartu_berlaku_sampai');
        });
    }
};
