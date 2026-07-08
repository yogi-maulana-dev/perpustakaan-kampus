<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // anggota
            $table->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete(); // petugas
            $table->date('dari_tanggal')->nullable();  // masa berlaku lama
            $table->date('sampai_tanggal');            // masa berlaku baru
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_renewals');
    }
};
