<?php

namespace App\Imports;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import buku dari Excel/CSV. Kolom minimal: Judul Buku.
 * Kolom lain (Nama Pengarang, Penerbit, Tahun Terbit, Cet/ED, Kategori, ISBN, Stok, Kode Buku)
 * bersifat opsional — nilai kosong akan diisi default / dibuat otomatis.
 */
class BooksImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped = 0;

    /** @var array<string,int> cache nama → id agar tidak query berulang */
    private array $authorCache = [];
    private array $publisherCache = [];
    private array $categoryCache = [];

    private int $counter = 0;

    public function collection(Collection $rows): void
    {
        $this->counter = (int) (Book::max('id') ?? 0);

        foreach ($rows as $row) {
            $row = $row->toArray();

            $judul = $this->pick($row, ['judul buku', 'judul', 'title']);
            if (! $judul) {
                $this->skipped++;
                continue;
            }

            $pengarang = $this->pick($row, ['nama pengarang', 'pengarang', 'penulis', 'author']) ?? 'Tanpa Nama Pengarang';
            $penerbit = $this->pick($row, ['penerbit', 'publisher']) ?? 'Tanpa Penerbit';
            $kategori = $this->pick($row, ['kategori', 'category']);
            $isbn = $this->pick($row, ['isbn']);
            $cetakan = $this->pick($row, ['cet/ed', 'cet ed', 'cetakan', 'edisi', 'cet', 'ed']);
            $kode = $this->pick($row, ['kode buku', 'kode']);

            $tahunRaw = $this->pick($row, ['tahun terbit', 'tahun', 'year']);
            $tahun = $tahunRaw ? (int) preg_replace('/\D/', '', $tahunRaw) : null;
            if ($tahun !== null && ($tahun < 1500 || $tahun > (int) date('Y') + 1)) {
                $tahun = null;
            }

            $stokRaw = $this->pick($row, ['stok', 'jumlah stok', 'jumlah', 'eksemplar']);
            $stok = ($stokRaw === null) ? 1 : max(0, (int) preg_replace('/\D/', '', $stokRaw));

            Book::create([
                'kode_buku' => $this->uniqueKode($kode),
                'isbn' => $isbn,
                'judul' => $judul,
                'category_id' => $this->categoryId($kategori),
                'author_id' => $this->authorId($pengarang),
                'publisher_id' => $this->publisherId($penerbit),
                'tahun_terbit' => $tahun,
                'cetakan' => $cetakan,
                'jumlah_stok' => $stok,
                'stok_tersedia' => $stok,
            ]);

            $this->imported++;
        }
    }

    /** Ambil nilai kolom berdasarkan beberapa kemungkinan nama (tahan spasi/format). */
    private function pick(array $row, array $aliases): ?string
    {
        $norm = [];
        foreach ($row as $k => $v) {
            $norm[preg_replace('/[^a-z0-9]/', '', strtolower((string) $k))] = $v;
        }

        foreach ($aliases as $alias) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower($alias));
            if (isset($norm[$key]) && trim((string) $norm[$key]) !== '') {
                return trim((string) $norm[$key]);
            }
        }

        return null;
    }

    private function uniqueKode(?string $kode): string
    {
        if ($kode && ! Book::where('kode_buku', $kode)->exists()) {
            return $kode;
        }

        do {
            $this->counter++;
            $candidate = 'BK-'.str_pad((string) $this->counter, 5, '0', STR_PAD_LEFT);
        } while (Book::where('kode_buku', $candidate)->exists());

        return $candidate;
    }

    private function authorId(string $nama): int
    {
        return $this->authorCache[$nama] ??= Author::firstOrCreate(['nama' => $nama])->id;
    }

    private function publisherId(string $nama): int
    {
        return $this->publisherCache[$nama] ??= Publisher::firstOrCreate(['nama' => $nama])->id;
    }

    private function categoryId(?string $nama): int
    {
        $nama = $nama ?: 'Umum';

        return $this->categoryCache[$nama] ??= Category::firstOrCreate(['nama' => $nama])->id;
    }
}
