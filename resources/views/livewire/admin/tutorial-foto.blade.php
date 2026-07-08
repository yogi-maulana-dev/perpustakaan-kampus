<?php

use App\Enums\RoleName;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.dashboard')] class extends Component {
    public bool $aktif = true;

    public string $url = '';

    public string $website = '';

    public string $text = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(RoleName::SuperAdmin->value), 403);

        $this->aktif = (bool) Setting::get('tutorial_foto_aktif', 1);
        $this->url = (string) Setting::get('tutorial_foto_url', '');
        $this->website = (string) Setting::get('tutorial_foto_website', 'https://imgwus.com/id/pas-foto-cpns');
        $this->text = (string) Setting::get(
            'tutorial_foto_text',
            'Untuk mempermudah Anda menjadikan foto ukuran 3×4, Anda bisa mengunjungi website ini dan melihat tutorialnya dengan klik tombol di bawah ini.'
        );
    }

    public function save(): void
    {
        $this->validate([
            'aktif' => ['boolean'],
            'url' => ['nullable', 'url', 'max:500', 'regex:~^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/~i'],
            'website' => ['nullable', 'url', 'max:500'],
            'text' => ['required', 'string', 'max:1000'],
        ], [
            'url.regex' => 'Link harus berupa tautan YouTube (youtube.com atau youtu.be).',
        ], [
            'url' => 'link YouTube',
            'website' => 'link website',
            'text' => 'tulisan',
        ]);

        Setting::set('tutorial_foto_aktif', $this->aktif ? 1 : 0);
        Setting::set('tutorial_foto_url', trim($this->url));
        Setting::set('tutorial_foto_website', trim($this->website));
        Setting::set('tutorial_foto_text', trim($this->text));

        $this->dispatch('toast', type: 'success', message: 'Pengaturan tutorial foto disimpan.');
    }

    /** URL embed YouTube dari link yang tersimpan (untuk pratinjau). */
    public function with(): array
    {
        $embed = '';
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~i', $this->url, $m)) {
            $embed = 'https://www.youtube.com/embed/'.$m[1];
        }

        return ['embed' => $embed];
    }
}; ?>

<div class="max-w-xl space-y-6">
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Tutorial Ganti Ukuran Foto</h3>
        <p class="mt-1 text-sm text-gray-500">
            Modal ini muncul otomatis di halaman <strong>Lengkapi Foto</strong> (anggota baru) berisi tulisan
            di bawah dan tombol menuju video tutorial YouTube cara membuat foto ukuran 3×4.
        </p>

        <form wire:submit="save" class="mt-6 space-y-5">
            <div class="rounded-lg bg-gray-50 p-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input wire:model="aktif" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                    Tampilkan modal tutorial di halaman Lengkapi Foto
                </label>
                <p class="mt-1 text-xs text-gray-400">Modal hanya muncul bila diaktifkan dan link YouTube terisi.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Link Website Ubah Ukuran Foto</label>
                <input wire:model="website" type="url" placeholder="https://imgwus.com/id/pas-foto-cpns"
                       class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('website') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                <p class="mt-1 text-xs text-gray-400">Website tempat anggota mengubah ukuran foto menjadi 3×4 (tombol hijau pada modal).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Link Video YouTube</label>
                <input wire:model.live.debounce.500ms="url" type="url" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx"
                       class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                @error('url') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                <p class="mt-1 text-xs text-gray-400">Mendukung format youtube.com/watch, youtu.be, dan YouTube Shorts.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tulisan pada Modal</label>
                <textarea wire:model="text" rows="3"
                          class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                @error('text') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div class="border-t pt-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    {{-- Pratinjau video --}}
    @if ($embed)
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800">Pratinjau Video</h3>
            <div class="mt-4 aspect-video overflow-hidden rounded-lg bg-gray-100">
                <iframe src="{{ $embed }}" class="h-full w-full" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
        </div>
    @endif
</div>
