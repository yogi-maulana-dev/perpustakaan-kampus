<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // UUID untuk URL publik /koleksi/{uuid} — id numerik tidak diekspos.
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Isi UUID untuk seluruh buku yang sudah ada.
        DB::table('books')->whereNull('uuid')->pluck('id')->each(function ($id) {
            DB::table('books')->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
