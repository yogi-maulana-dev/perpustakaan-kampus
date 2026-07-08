<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->string('tipe')->default('mahasiswa')->index()->after('user_id');
            $table->string('nidn')->nullable()->after('nim');           // Dosen
            $table->string('nomor_identitas')->nullable()->after('nidn'); // Umum (KTP)
            $table->string('pekerjaan')->nullable()->after('angkatan');   // Umum
            $table->string('instansi')->nullable()->after('pekerjaan');   // Umum/Dosen
        });

        // Field mahasiswa kini opsional (tergantung tipe anggota).
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->string('nim')->nullable()->change();
            $table->string('fakultas')->nullable()->change();
            $table->string('program_studi')->nullable()->change();
            $table->string('angkatan', 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'nidn', 'nomor_identitas', 'pekerjaan', 'instansi']);
        });
    }
};
