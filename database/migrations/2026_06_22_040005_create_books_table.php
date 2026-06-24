<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('kode_buku')->unique();
            $table->string('isbn')->nullable();
            $table->string('judul');
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('publisher_id')->constrained('publishers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shelf_id')->nullable()->constrained('shelves')->cascadeOnUpdate()->nullOnDelete();
            $table->year('tahun_terbit')->nullable();
            $table->unsignedInteger('jumlah_stok')->default(0);
            $table->unsignedInteger('stok_tersedia')->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('cover')->nullable();
            $table->timestamps();

            $table->index('judul');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
