<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public $foto = null;

    public function mount(): void
    {
        // Sudah punya foto → tidak perlu di sini.
        if (optional(auth()->user()->mahasiswaProfile)->foto) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function save(): void
    {
        $this->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [], ['foto' => 'foto']);

        $profile = auth()->user()->mahasiswaProfile;

        if ($profile->foto) {
            Storage::disk('public')->delete($profile->foto);
        }

        $profile->update(['foto' => $this->foto->store('foto-anggota', 'public')]);

        session()->flash('status', 'Foto berhasil diunggah. Selamat datang!');
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
            <span class="font-semibold">Lengkapi foto Anda dulu ya.</span> Foto ini dipakai untuk
            <strong>kartu anggota</strong> perpustakaan dan wajib diunggah sebelum melanjutkan.
        </div>

        <h2 class="text-lg font-bold text-emerald-900">Upload Pas Foto</h2>
        <p class="mt-1 text-sm text-gray-500">Disarankan <strong>pas foto 3×4</strong> (mis. 300 × 400 px), wajah jelas & tegak, format JPG/PNG, maks 2 MB.</p>

        {{-- Tombol + modal tutorial (muncul otomatis, dikelola Super Admin) --}}
        <div class="mt-3">
            @include('partials.modal-tutorial-foto')
        </div>

        <form wire:submit="save" class="mt-6 space-y-4">
            <div class="flex items-start gap-4">
                {{-- Preview rasio 3:4 --}}
                <div class="h-40 w-32 shrink-0 overflow-hidden rounded-lg border-2 border-dashed border-emerald-200 bg-emerald-50">
                    @if ($foto)
                        <img src="{{ $foto->temporaryUrl() }}" class="h-full w-full object-cover" />
                    @else
                        <div class="grid h-full place-items-center text-center text-xs text-emerald-400">Pratinjau<br>3 × 4</div>
                    @endif
                </div>

                <div class="flex-1">
                    <input wire:model="foto" type="file" accept="image/png,image/jpeg"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                    <div wire:loading wire:target="foto" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                    @error('foto') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    <p class="mt-2 text-xs text-gray-400">Tips: gunakan latar polos & pencahayaan cukup agar foto kartu rapi.</p>
                </div>
            </div>

            <div class="border-t pt-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="save,foto"
                        class="w-full rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                    Simpan Foto & Lanjutkan
                </button>
            </div>
        </form>
    </div>
</div>
