<?php

use App\Enums\RoleName;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.dashboard')] class extends Component {
    public string $alamat = '';
    public string $telepon = '';
    public string $email = '';
    public string $jam = '';
    public string $whatsapp = '';
    public string $instagram = '';
    public string $facebook = '';
    public string $maps = '';

    public function mount(): void
    {
        // Hanya Admin & Super Admin — staf lain tidak boleh.
        abort_unless(auth()->user()->hasAnyRole(RoleName::managerRoles()), 403);

        $this->alamat = (string) Setting::get('kontak_alamat', '');
        $this->telepon = (string) Setting::get('kontak_telepon', '');
        $this->email = (string) Setting::get('kontak_email', '');
        $this->jam = (string) Setting::get('kontak_jam', '');
        $this->whatsapp = (string) Setting::get('wa_number', ''); // sama dengan nomor WA di Pengaturan
        $this->instagram = (string) Setting::get('kontak_instagram', '');
        $this->facebook = (string) Setting::get('kontak_facebook', '');
        $this->maps = (string) Setting::get('kontak_maps', '');
    }

    public function save(): void
    {
        $this->validate([
            'alamat' => ['nullable', 'string', 'max:500'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'jam' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'maps' => ['nullable', 'url', 'max:1000'],
        ], [], [
            'whatsapp' => 'nomor WhatsApp',
        ]);

        Setting::set('kontak_alamat', trim($this->alamat));
        Setting::set('kontak_telepon', trim($this->telepon));
        Setting::set('kontak_email', trim($this->email));
        Setting::set('kontak_jam', trim($this->jam));
        Setting::set('wa_number', trim($this->whatsapp));
        Setting::set('kontak_instagram', trim($this->instagram));
        Setting::set('kontak_facebook', trim($this->facebook));
        Setting::set('kontak_maps', trim($this->maps));

        $this->dispatch('toast', type: 'success', message: 'Informasi kontak disimpan.');
    }
}; ?>

<div class="max-w-xl space-y-6">
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Informasi Kontak</h3>
        <p class="mt-1 text-sm text-gray-500">Tampil di bagian <strong>Kontak</strong> halaman depan. Hanya Admin & Super Admin yang bisa mengubah.</p>

        <form wire:submit="save" class="mt-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Alamat</label>
                <textarea wire:model="alamat" rows="2" placeholder="Jl. ZA. Pagar Alam, Labuhan Ratu, Kedaton, Bandar Lampung"
                          class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                @error('alamat') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                    <input wire:model="telepon" type="text" placeholder="0721-xxxxxx"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('telepon') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                    <input wire:model="whatsapp" type="text" placeholder="0812xxxxxxxx atau 62812xxxxxxxx"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('whatsapp') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-400">Dipakai juga untuk tombol WhatsApp & pengajuan pinjam.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input wire:model="email" type="email" placeholder="perpustakaan@uml.ac.id"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jam Operasional</label>
                    <input wire:model="jam" type="text" placeholder="Senin–Jumat, 08.00–16.00"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('jam') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-700">Media Sosial & Lokasi (opsional)</p>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Instagram (URL / username)</label>
                        <input wire:model="instagram" type="text" placeholder="https://instagram.com/perpus.uml atau @perpus.uml"
                               class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('instagram') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Facebook (URL)</label>
                        <input wire:model="facebook" type="text" placeholder="https://facebook.com/perpustakaanUML"
                               class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('facebook') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Google Maps (link / embed URL)</label>
                        <input wire:model="maps" type="url" placeholder="https://maps.google.com/... atau link embed"
                               class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('maps') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <p class="mt-1 text-xs text-gray-400">Tempel tautan Google Maps lokasi perpustakaan. Bila memakai URL "embed", peta tampil langsung di halaman depan.</p>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan Kontak</button>
            </div>
        </form>
    </div>
</div>
