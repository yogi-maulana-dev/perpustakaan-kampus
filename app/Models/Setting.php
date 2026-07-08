<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /** URL logo aplikasi: dari setting (upload) jika ada, jika tidak pakai file default. */
    public static function logoUrl(): string
    {
        $path = static::get('logo_path');

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset('images/logo-uml.png');
    }

    /** URL foto rektor (cutout). Dari setting bila ada, fallback file images/rektor.png. */
    public static function rektorUrl(): string
    {
        $path = static::get('rektor_path');

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset('images/rektor.png');
    }

    /** Teks tata tertib bawaan untuk sisi belakang kartu anggota. */
    public static function kartuTataTertibDefault(): string
    {
        return "Kartu anggota adalah kartu identitas resmi anggota Perpustakaan Universitas Muhammadiyah Lampung.\n"
            ."Kartu tidak boleh dipinjamkan atau dipergunakan oleh orang lain.\n"
            ."Kartu wajib dibawa dan ditunjukkan setiap meminjam maupun mengembalikan buku.\n"
            ."Peminjam wajib menjaga dan merawat buku yang dipinjam.\n"
            ."Keterlambatan pengembalian buku dikenakan denda sesuai ketentuan yang berlaku.\n"
            ."Kehilangan kartu harap segera dilaporkan kepada petugas perpustakaan.";
    }

    /** Data sisi belakang kartu anggota (tata tertib, kota, pejabat & tanda tangan). */
    public static function kartuBelakang(): array
    {
        $ttdPath = static::get('kartu_ttd_path');

        return [
            'tata_tertib' => array_values(array_filter(array_map('trim', preg_split(
                '/\r\n|\r|\n/',
                (string) static::get('kartu_tata_tertib', static::kartuTataTertibDefault())
            )))),
            'kota' => (string) static::get('kartu_kota', 'Bandar Lampung'),
            'jabatan' => (string) static::get('kartu_jabatan', 'Kepala Perpustakaan'),
            'nama' => (string) static::get('kartu_nama', ''),
            'nip' => (string) static::get('kartu_nip', ''),
            'ttd_url' => ($ttdPath && Storage::disk('public')->exists($ttdPath))
                ? Storage::disk('public')->url($ttdPath)
                : null,
        ];
    }

    /** URL gambar struktur organisasi (opsional). Null bila belum diunggah. */
    public static function strukturUrl(): ?string
    {
        $path = static::get('struktur_foto');

        return ($path && Storage::disk('public')->exists($path))
            ? Storage::disk('public')->url($path)
            : null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Nomor WA untuk perpanjangan kartu (halaman keanggotaan kadaluarsa).
     * Bila kosong, memakai nomor WA perpustakaan utama.
     */
    public static function waPerpanjanganNumber(): ?string
    {
        $raw = preg_replace('/\D/', '', (string) static::get('wa_perpanjangan'));

        if (! $raw) {
            return static::waNumber();
        }

        if (str_starts_with($raw, '0')) {
            $raw = '62'.substr($raw, 1);
        }

        return $raw;
    }

    /** Nomor WA dinormalkan ke format internasional (62...). */
    public static function waNumber(): ?string
    {
        $raw = preg_replace('/\D/', '', (string) static::get('wa_number'));

        if (! $raw) {
            return null;
        }

        if (str_starts_with($raw, '0')) {
            $raw = '62'.substr($raw, 1);
        }

        return $raw;
    }

    /**
     * Bangun URL wa.me dengan pesan dari template. Mengembalikan null bila nomor kosong.
     *
     * @param  array<string, string>  $vars  placeholder => nilai (mis. ['nama' => 'Budi'])
     */
    public static function waUrl(array $vars = []): ?string
    {
        $number = static::waNumber();

        if (! $number) {
            return null;
        }

        $template = (string) static::get('wa_template', 'Halo, saya ingin mengajukan peminjaman buku "{judul}". Terima kasih.');

        $search = array_map(fn ($k) => '{'.$k.'}', array_keys($vars));
        $text = str_replace($search, array_values($vars), $template);

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }
}
