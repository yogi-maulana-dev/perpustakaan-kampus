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
