<?php

use App\Enums\RoleName;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public int $tarif_denda = 0;
    public int $durasi_pinjam = 0;
    public int $max_pinjam = 0;
    public bool $perpanjangan_aktif = true;
    public int $max_perpanjangan = 0;
    public bool $notif_anggota_aktif = true;
    public int $notif_anggota_interval = 2;
    public string $wa_number = '';
    public string $wa_template = '';
    public $logo = null;
    public $rektor = null;
    public string $rektor_nama = '';

    // Kartu anggota — masa berlaku & sisi belakang
    public int $masa_berlaku_kartu = 5;
    public string $wa_perpanjangan = '';
    public string $kartu_kota = '';
    public string $kartu_jabatan = '';
    public string $kartu_nama = '';
    public string $kartu_nip = '';
    public string $kartu_tata_tertib = '';
    public $ttd = null;

    // Email / SMTP
    public string $mail_host = '';
    public string $mail_port = '';
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = '';
    public string $mail_from_address = '';
    public string $mail_from_name = '';
    public string $mail_reset_subject = '';
    public string $mail_reset_body = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(RoleName::managerRoles()), 403);
        $this->tarif_denda = (int) Setting::get('tarif_denda', 1000);
        $this->durasi_pinjam = (int) Setting::get('durasi_pinjam', 7);
        $this->max_pinjam = (int) Setting::get('max_pinjam', 3);
        $this->perpanjangan_aktif = (bool) Setting::get('perpanjangan_aktif', 1);
        $this->max_perpanjangan = (int) Setting::get('max_perpanjangan', 2);
        $this->notif_anggota_aktif = (bool) Setting::get('notif_anggota_aktif', 1);
        $this->notif_anggota_interval = (int) Setting::get('notif_anggota_interval', 2);
        $this->wa_number = (string) Setting::get('wa_number', '');
        $this->wa_template = (string) Setting::get('wa_template', '');
        $this->rektor_nama = (string) Setting::get('rektor_nama', '');

        $this->masa_berlaku_kartu = (int) Setting::get('masa_berlaku_kartu', 5);
        $this->wa_perpanjangan = (string) Setting::get('wa_perpanjangan', '');
        $this->kartu_kota = (string) Setting::get('kartu_kota', 'Bandar Lampung');
        $this->kartu_jabatan = (string) Setting::get('kartu_jabatan', 'Kepala Perpustakaan');
        $this->kartu_nama = (string) Setting::get('kartu_nama', '');
        $this->kartu_nip = (string) Setting::get('kartu_nip', '');
        $this->kartu_tata_tertib = (string) Setting::get('kartu_tata_tertib', Setting::kartuTataTertibDefault());

        $this->mail_host = (string) Setting::get('mail_host', config('mail.mailers.smtp.host'));
        $this->mail_port = (string) Setting::get('mail_port', config('mail.mailers.smtp.port'));
        $this->mail_username = (string) Setting::get('mail_username', config('mail.mailers.smtp.username'));
        $this->mail_password = (string) Setting::get('mail_password', config('mail.mailers.smtp.password'));
        $this->mail_encryption = (string) Setting::get('mail_encryption', '');
        $this->mail_from_address = (string) Setting::get('mail_from_address', config('mail.from.address'));
        $this->mail_from_name = (string) Setting::get('mail_from_name', config('mail.from.name'));
        $this->mail_reset_subject = (string) Setting::get('mail_reset_subject', 'Atur Ulang Password — Perpustakaan UML');
        $this->mail_reset_body = (string) Setting::get('mail_reset_body', 'Anda menerima email ini karena ada permintaan reset password untuk akun Anda di Perpustakaan UML.');
    }

    protected function mailRules(): array
    {
        return [
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'in:tls,ssl'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_reset_subject' => ['nullable', 'string', 'max:255'],
            'mail_reset_body' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function saveMail(): void
    {
        $this->validate($this->mailRules());

        foreach (['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name', 'mail_reset_subject', 'mail_reset_body'] as $k) {
            Setting::set($k, $this->$k);
        }

        $this->dispatch('toast', type: 'success', message: 'Pengaturan email disimpan.');
    }

    public function testMail(): void
    {
        $this->validate($this->mailRules());

        // Terapkan nilai form saat ini untuk uji coba.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->mail_host,
            'mail.mailers.smtp.port' => (int) $this->mail_port,
            'mail.mailers.smtp.username' => $this->mail_username ?: null,
            'mail.mailers.smtp.password' => $this->mail_password ?: null,
            'mail.mailers.smtp.scheme' => $this->mail_encryption === 'ssl' ? 'smtps' : null,
            'mail.from.address' => $this->mail_from_address,
            'mail.from.name' => $this->mail_from_name,
        ]);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Email uji dari Sistem Informasi Perpustakaan UML. Jika Anda menerima pesan ini, konfigurasi SMTP sudah benar. ✅',
                fn ($m) => $m->to(auth()->user()->email)->subject('Tes Email — Perpustakaan UML')
            );
            $this->dispatch('toast', type: 'success', message: 'Email uji terkirim ke '.auth()->user()->email);
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Gagal kirim: '.\Illuminate\Support\Str::limit($e->getMessage(), 120));
        }
    }

    public function saveRektor(): void
    {
        $this->validate([
            'rektor' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'rektor_nama' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->rektor) {
            $old = Setting::get('rektor_path');
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            Setting::set('rektor_path', $this->rektor->store('rektor', 'public'));
        }

        Setting::set('rektor_nama', $this->rektor_nama);

        $this->rektor = null;
        $this->dispatch('toast', type: 'success', message: 'Data rektor diperbarui.');
    }

    public function resetRektor(): void
    {
        $old = Setting::get('rektor_path');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        Setting::set('rektor_path', null);
        $this->dispatch('toast', type: 'success', message: 'Foto rektor dihapus.');
    }

    public function saveKartu(): void
    {
        $this->validate([
            'masa_berlaku_kartu' => ['required', 'integer', 'min:1', 'max:20'],
            'wa_perpanjangan' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'kartu_kota' => ['required', 'string', 'max:100'],
            'kartu_jabatan' => ['required', 'string', 'max:150'],
            'kartu_nama' => ['nullable', 'string', 'max:150'],
            'kartu_nip' => ['nullable', 'string', 'max:50'],
            'kartu_tata_tertib' => ['required', 'string', 'max:2000'],
            'ttd' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ], [], [
            'masa_berlaku_kartu' => 'masa berlaku kartu',
            'wa_perpanjangan' => 'nomor WhatsApp perpanjangan',
            'kartu_kota' => 'kota',
            'kartu_jabatan' => 'jabatan',
            'kartu_nama' => 'nama',
            'kartu_nip' => 'NIP',
            'kartu_tata_tertib' => 'tata tertib',
            'ttd' => 'tanda tangan',
        ]);

        if ($this->ttd) {
            $old = Setting::get('kartu_ttd_path');
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            Setting::set('kartu_ttd_path', $this->ttd->store('ttd', 'public'));
        }

        Setting::set('masa_berlaku_kartu', $this->masa_berlaku_kartu);
        Setting::set('wa_perpanjangan', trim($this->wa_perpanjangan));
        Setting::set('kartu_kota', trim($this->kartu_kota));
        Setting::set('kartu_jabatan', trim($this->kartu_jabatan));
        Setting::set('kartu_nama', trim($this->kartu_nama));
        Setting::set('kartu_nip', trim($this->kartu_nip));
        Setting::set('kartu_tata_tertib', trim($this->kartu_tata_tertib));

        $this->ttd = null;
        $this->dispatch('toast', type: 'success', message: 'Pengaturan kartu anggota disimpan.');
    }

    public function resetTtd(): void
    {
        $old = Setting::get('kartu_ttd_path');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        Setting::set('kartu_ttd_path', null);
        $this->dispatch('toast', type: 'success', message: 'Tanda tangan dihapus.');
    }

    public function save(): void
    {
        $this->validate([
            'tarif_denda' => ['required', 'integer', 'min:0'],
            'durasi_pinjam' => ['required', 'integer', 'min:1'],
            'max_pinjam' => ['required', 'integer', 'min:1'],
            'max_perpanjangan' => ['required', 'integer', 'min:0'],
            'perpanjangan_aktif' => ['boolean'],
            'notif_anggota_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'notif_anggota_aktif' => ['boolean'],
            'wa_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'wa_template' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::set('tarif_denda', $this->tarif_denda);
        Setting::set('durasi_pinjam', $this->durasi_pinjam);
        Setting::set('max_pinjam', $this->max_pinjam);
        Setting::set('max_perpanjangan', $this->max_perpanjangan);
        Setting::set('perpanjangan_aktif', $this->perpanjangan_aktif ? 1 : 0);
        Setting::set('notif_anggota_aktif', $this->notif_anggota_aktif ? 1 : 0);
        Setting::set('notif_anggota_interval', $this->notif_anggota_interval);
        Setting::set('wa_number', trim($this->wa_number));
        Setting::set('wa_template', $this->wa_template);

        $this->dispatch('toast', type: 'success', message: 'Pengaturan disimpan.');
    }

    public function saveLogo(): void
    {
        $this->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1024'],
        ]);

        // Hapus logo lama bila ada.
        $old = Setting::get('logo_path');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $this->logo->store('logo', 'public');
        Setting::set('logo_path', $path);

        $this->logo = null;
        $this->dispatch('toast', type: 'success', message: 'Logo diperbarui.');
    }

    public function resetLogo(): void
    {
        $old = Setting::get('logo_path');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        Setting::set('logo_path', null);
        $this->dispatch('toast', type: 'success', message: 'Logo dikembalikan ke default.');
    }
}; ?>

<div class="max-w-xl space-y-6">
    {{-- Logo aplikasi --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Logo Aplikasi</h3>
        <p class="mt-1 text-sm text-gray-500">Logo tampil di halaman depan & login. Disarankan PNG transparan.</p>

        <div class="mt-4 flex items-center gap-4">
            <div class="grid h-20 w-44 place-items-center rounded-lg border bg-emerald-900 p-2">
                <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo" class="max-h-full max-w-full object-contain"
                     onerror="this.style.display='none'">
            </div>
            <div class="flex-1">
                <input wire:model="logo" type="file" accept="image/*"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                <div wire:loading wire:target="logo" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                @error('logo') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" class="mt-2 h-12 object-contain" />
                @endif
                <div class="mt-3 flex gap-2">
                    <button wire:click="saveLogo" wire:loading.attr="disabled" wire:target="saveLogo,logo"
                            class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">Simpan Logo</button>
                    <button wire:click="resetLogo" wire:confirm="Kembalikan logo ke default?"
                            class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Reset Default</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Parameter sistem --}}
    {{-- Foto & Nama Rektor --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Foto & Nama Rektor</h3>
        <p class="mt-1 text-sm text-gray-500">Tampil di hero halaman depan (menyatu dengan background). Disarankan
            <strong>PNG transparan (cutout, background dihapus)</strong>, potret <strong>800 × 1100 px</strong>, maks 3 MB.</p>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="grid h-44 w-36 place-items-center overflow-hidden rounded-lg bg-gradient-to-b from-emerald-100 to-emerald-50 ring-1 ring-emerald-100">
                @if ($rektor)
                    <img src="{{ $rektor->temporaryUrl() }}" class="h-full w-full object-contain object-bottom" />
                @else
                    <img src="{{ \App\Models\Setting::rektorUrl() }}" class="h-full w-full object-contain object-bottom"
                         onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'text-xs text-emerald-400 px-2 text-center',innerText:'Belum ada foto rektor'}))" />
                @endif
            </div>
            <div class="flex-1 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Rektor (beserta gelar)</label>
                    <input wire:model="rektor_nama" type="text" placeholder="Dr. H. Nama Rektor, M.Pd."
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('rektor_nama') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Foto Rektor (PNG transparan)</label>
                    <input wire:model="rektor" type="file" accept="image/png,image/webp,image/jpeg"
                           class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                    <div wire:loading wire:target="rektor" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                    @error('rektor') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="saveRektor" wire:loading.attr="disabled" wire:target="saveRektor,rektor"
                            class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">Simpan Rektor</button>
                    <button wire:click="resetRektor" wire:confirm="Hapus foto rektor?"
                            class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Hapus Foto</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu anggota — sisi belakang --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Kartu Anggota — Sisi Belakang</h3>
        <p class="mt-1 text-sm text-gray-500">Tata tertib & bagian pengesahan (kota, tanggal, nama, tanda tangan, NIP) yang tampil
            di sisi belakang kartu saat cetak kartu anggota. Tanggal mengikuti tanggal saat kartu dicetak.</p>

        <form wire:submit="saveKartu" class="mt-6 space-y-4">
            <div class="rounded-lg bg-emerald-50 p-4">
                <label class="block text-sm font-medium text-gray-700">Masa Berlaku Keanggotaan (tahun)</label>
                <input wire:model="masa_berlaku_kartu" type="number" min="1" max="20"
                       class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('masa_berlaku_kartu') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                <p class="mt-1 text-xs text-emerald-700">Dihitung sejak tanggal daftar (atau sejak diperpanjang). Anggota yang lewat masa berlaku
                    tidak bisa mengakses akun sampai kartunya diperpanjang petugas di menu <strong>Data Anggota</strong>.</p>

                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp Perpanjangan Kartu</label>
                    <input wire:model="wa_perpanjangan" type="text" placeholder="0812xxxxxxxx atau 62812xxxxxxxx"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('wa_perpanjangan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-400">Dipakai tombol "Hubungi Staff via WhatsApp" di halaman keanggotaan kadaluarsa.
                        Kosongkan untuk memakai Nomor WhatsApp Perpustakaan (di bagian Parameter Peminjaman & Denda).</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tata Tertib (satu aturan per baris)</label>
                <textarea wire:model="kartu_tata_tertib" rows="6"
                          class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                @error('kartu_tata_tertib') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                <p class="mt-1 text-xs text-gray-400">Setiap baris otomatis diberi nomor urut pada kartu.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kota</label>
                    <input wire:model="kartu_kota" type="text" placeholder="Bandar Lampung"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('kartu_kota') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <input wire:model="kartu_jabatan" type="text" placeholder="Kepala Perpustakaan"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('kartu_jabatan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama (beserta gelar)</label>
                    <input wire:model="kartu_nama" type="text" placeholder="Dr. H. Nama Kepala, M.Pd."
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('kartu_nama') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">NIP</label>
                    <input wire:model="kartu_nip" type="text" placeholder="19xxxxxxxxxxxxxxxxx"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('kartu_nip') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Gambar Tanda Tangan (opsional, PNG transparan)</label>
                <div class="mt-1 flex items-center gap-3">
                    @php $ttdSaved = \App\Models\Setting::kartuBelakang()['ttd_url']; @endphp
                    <div class="grid h-16 w-28 shrink-0 place-items-center overflow-hidden rounded-lg border bg-gray-50">
                        @if ($ttd)
                            <img src="{{ $ttd->temporaryUrl() }}" class="max-h-full max-w-full object-contain" />
                        @elseif ($ttdSaved)
                            <img src="{{ $ttdSaved }}" class="max-h-full max-w-full object-contain" onerror="this.remove()" />
                        @else
                            <span class="px-2 text-center text-[10px] text-gray-400">Belum ada tanda tangan</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input wire:model="ttd" type="file" accept="image/png,image/jpeg,image/webp"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                        <div wire:loading wire:target="ttd" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                        @error('ttd') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <p class="mt-1 text-xs text-gray-400">Kosongkan bila tanda tangan dilakukan manual setelah kartu dicetak.</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 border-t pt-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveKartu,ttd"
                        class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">Simpan Kartu</button>
                @if ($ttdSaved)
                    <button type="button" wire:click="resetTtd" wire:confirm="Hapus gambar tanda tangan?"
                            class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Hapus Tanda Tangan</button>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Parameter Peminjaman & Denda</h3>
        <p class="mt-1 text-sm text-gray-500">Dipakai seluruh transaksi.</p>

        <form wire:submit="save" class="mt-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tarif Denda (Rp / hari)</label>
                <input wire:model="tarif_denda" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('tarif_denda') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Durasi Peminjaman (hari)</label>
                <input wire:model="durasi_pinjam" type="number" min="1" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('durasi_pinjam') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Maksimal Peminjaman Aktif</label>
                <input wire:model="max_pinjam" type="number" min="1" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('max_pinjam') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div class="rounded-lg bg-gray-50 p-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input wire:model="perpanjangan_aktif" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                    Izinkan perpanjangan buku
                </label>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">Batas Maksimal Perpanjangan (kali)</label>
                    <input wire:model="max_perpanjangan" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('max_perpanjangan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-400">Setiap perpanjangan menambah masa pinjam sesuai durasi peminjaman.</p>
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 p-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input wire:model="notif_anggota_aktif" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                    Tampilkan notifikasi pendaftar anggota baru (real-time)
                </label>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">Refresh notifikasi tiap (menit)</label>
                    <input wire:model="notif_anggota_interval" type="number" min="1" max="60" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('notif_anggota_interval') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-400">Menampilkan 5 pendaftar terbaru. Interval lebih besar = server lebih ringan. Ubah lalu muat ulang halaman agar interval baru berlaku.</p>
                </div>
            </div>

            <div class="rounded-lg bg-emerald-50 p-4">
                <p class="text-sm font-semibold text-emerald-800">Pengajuan via WhatsApp</p>
                <p class="mt-0.5 text-xs text-emerald-700">Mahasiswa bisa mengajukan pinjam lewat WA selain lewat sistem.</p>

                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp Perpustakaan</label>
                    <input wire:model="wa_number" type="text" placeholder="0812xxxxxxxx atau 62812xxxxxxxx"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('wa_number') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-400">Kosongkan untuk menyembunyikan tombol WhatsApp.</p>
                </div>

                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">Template Pesan</label>
                    <textarea wire:model="wa_template" rows="3"
                              class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    @error('wa_template') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-400">Placeholder tersedia: <code>{nama}</code>, <code>{identitas}</code>, <code>{judul}</code>, <code>{kode}</code>.</p>
                </div>
            </div>

            <div class="border-t pt-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    {{-- Pengaturan Email (SMTP) --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Pengaturan Email (SMTP)</h3>
        <p class="mt-1 text-sm text-gray-500">Untuk kirim email reset password & notifikasi. Bisa diubah dari sini tanpa edit file server. Sekarang pakai Mailtrap; ganti saat sudah di VPS.</p>

        <form wire:submit="saveMail" class="mt-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Host SMTP</label>
                    <input wire:model="mail_host" type="text" placeholder="sandbox.smtp.mailtrap.io" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('mail_host') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Port</label>
                    <input wire:model="mail_port" type="number" placeholder="2525" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('mail_port') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Enkripsi</label>
                    <select wire:model="mail_encryption" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Otomatis (STARTTLS)</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input wire:model="mail_username" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <div x-data="{ show: false }" class="relative mt-1">
                        <input wire:model="mail_password" x-bind:type="show ? 'text' : 'password'" class="w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        <button type="button" tabindex="-1" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" style="display:none" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 004.2 4.2"/><path d="M9.9 5.1A9.6 9.6 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3 4.1M6.2 6.2A16.8 16.8 0 002 12s3.5 7 10 7a9.5 9.5 0 003.9-.8"/></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Pengirim</label>
                    <input wire:model="mail_from_address" type="email" placeholder="no-reply@uml.ac.id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('mail_from_address') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Pengirim</label>
                    <input wire:model="mail_from_name" type="text" placeholder="Perpustakaan UML" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('mail_from_name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="border-t pt-4">
                <p class="text-sm font-semibold text-gray-700">Isi Email Reset Password</p>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subjek</label>
                        <input wire:model="mail_reset_subject" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('mail_reset_subject') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Isi Pesan</label>
                        <textarea wire:model="mail_reset_body" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        @error('mail_reset_body') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <p class="mt-1 text-xs text-gray-400">Tombol "Atur Ulang Password" & tautan token otomatis ditambahkan di bawah pesan.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t pt-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan Email</button>
                <button type="button" wire:click="testMail" wire:loading.attr="disabled" wire:target="testMail" class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Kirim Email Uji</button>
                <span wire:loading wire:target="testMail" class="text-sm text-gray-500">Mengirim…</span>
            </div>
        </form>
    </div>
</div>
