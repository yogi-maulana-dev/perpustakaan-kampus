<?php

use App\Enums\RoleName;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public string $visi = '';
    public string $misi = '';
    public string $sejarah = '';
    public string $struktur_keterangan = '';
    public $struktur = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(RoleName::managerRoles()), 403);

        $this->visi = (string) Setting::get('profil_visi', '');
        $this->misi = (string) Setting::get('profil_misi', '');
        $this->sejarah = (string) Setting::get('profil_sejarah', '');
        $this->struktur_keterangan = (string) Setting::get('struktur_keterangan', '');
    }

    public function saveVisiMisi(): void
    {
        $this->validate([
            'visi' => ['nullable', 'string', 'max:2000'],
            'misi' => ['nullable', 'string', 'max:4000'],
        ]);

        Setting::set('profil_visi', trim($this->visi));
        Setting::set('profil_misi', trim($this->misi));
        $this->dispatch('toast', type: 'success', message: 'Visi & Misi disimpan.');
    }

    public function saveSejarah(): void
    {
        $this->validate(['sejarah' => ['nullable', 'string', 'max:8000']]);

        Setting::set('profil_sejarah', trim($this->sejarah));
        $this->dispatch('toast', type: 'success', message: 'Sejarah disimpan.');
    }

    public function saveStruktur(): void
    {
        $this->validate([
            'struktur' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'struktur_keterangan' => ['nullable', 'string', 'max:500'],
        ], [], ['struktur' => 'gambar struktur']);

        if ($this->struktur) {
            $old = Setting::get('struktur_foto');
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            Setting::set('struktur_foto', $this->struktur->store('struktur', 'public'));
        }

        Setting::set('struktur_keterangan', trim($this->struktur_keterangan));
        $this->struktur = null;
        $this->dispatch('toast', type: 'success', message: 'Struktur organisasi disimpan.');
    }

    public function resetStruktur(): void
    {
        $old = Setting::get('struktur_foto');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        Setting::set('struktur_foto', null);
        $this->dispatch('toast', type: 'success', message: 'Gambar struktur dihapus.');
    }
}; ?>

<div class="max-w-2xl space-y-6">
    {{-- Visi & Misi --}}
    <div id="visi-misi" class="scroll-mt-6 rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Visi &amp; Misi</h3>
        <p class="mt-1 text-sm text-gray-500">Tampil di halaman <strong>Profil → Visi &amp; Misi</strong>.</p>
        <form wire:submit="saveVisiMisi" class="mt-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Visi</label>
                <textarea wire:model="visi" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                @error('visi') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Misi (satu poin per baris)</label>
                <textarea wire:model="misi" rows="6" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                @error('misi') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                <p class="mt-1 text-xs text-gray-400">Tiap baris otomatis jadi butir bernomor.</p>
            </div>
            <div class="border-t pt-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan Visi &amp; Misi</button>
            </div>
        </form>
    </div>

    {{-- Sejarah --}}
    <div id="sejarah" class="scroll-mt-6 rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Sejarah</h3>
        <p class="mt-1 text-sm text-gray-500">Tampil di halaman <strong>Profil → Sejarah</strong>. Pisahkan paragraf dengan baris kosong.</p>
        <form wire:submit="saveSejarah" class="mt-5 space-y-4">
            <div>
                <textarea wire:model="sejarah" rows="8" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                @error('sejarah') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div class="border-t pt-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan Sejarah</button>
            </div>
        </form>
    </div>

    {{-- Struktur Organisasi --}}
    <div id="struktur" class="scroll-mt-6 rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Struktur Organisasi</h3>
        <p class="mt-1 text-sm text-gray-500">Unggah <strong>gambar bagan struktur organisasi</strong>. Tampil di halaman
            <strong>Profil → Struktur Organisasi</strong>.</p>

        <div class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
            <p><span class="font-semibold">Ukuran gambar disarankan:</span> lebar <strong>1200–1600 px</strong> (rasio mendatar/landscape,
                mis. <strong>1600 × 1000 px</strong>) · format <strong>JPG / PNG</strong> · maks <strong>4 MB</strong>. Gunakan gambar tajam agar teks bagan terbaca.</p>
            {{-- Tombol + modal tutorial ganti ukuran foto (sama seperti di halaman Register) --}}
            <div class="mt-2">
                @include('partials.modal-tutorial-foto', ['autoOpen' => false])
            </div>
        </div>

        <form wire:submit="saveStruktur" class="mt-4 space-y-4">
            @php $strukturUrl = \App\Models\Setting::strukturUrl(); @endphp
            <div class="grid h-52 w-full place-items-center overflow-hidden rounded-lg border bg-gray-50">
                @if ($struktur)
                    <img src="{{ $struktur->temporaryUrl() }}" class="max-h-full max-w-full object-contain" />
                @elseif ($strukturUrl)
                    <img src="{{ $strukturUrl }}" class="max-h-full max-w-full object-contain" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'text-xs text-gray-400',innerText:'Gagal memuat'}))" />
                @else
                    <span class="px-3 text-center text-xs text-gray-400">Belum ada gambar struktur</span>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Gambar Struktur (JPG/PNG/WEBP · maks 4 MB)</label>
                <input wire:model="struktur" type="file" accept=".jpg,.jpeg,.png,.webp"
                       class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                <div wire:loading wire:target="struktur" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                @error('struktur') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Keterangan (opsional)</label>
                <input wire:model="struktur_keterangan" type="text" placeholder="mis. Struktur Organisasi Perpustakaan UML 2024/2025"
                       class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('struktur_keterangan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-2 border-t pt-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveStruktur,struktur"
                        class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">Simpan Struktur</button>
                @if ($strukturUrl)
                    <button type="button" wire:click="resetStruktur" wire:confirm="Hapus gambar struktur?"
                            class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Hapus Gambar</button>
                @endif
            </div>
        </form>
    </div>
</div>
