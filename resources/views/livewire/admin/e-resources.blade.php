<?php

use App\Models\Ekatalog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $judul = '';
    public string $deskripsi = '';
    public string $link = '';
    public int $urutan = 0;
    public bool $aktif = true;
    public $gambar = null;
    public ?string $existingGambar = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('kelola master'), 403);
    }

    protected function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'link' => ['nullable', 'url', 'max:500'],
            'urutan' => ['required', 'integer', 'min:0'],
            'aktif' => ['boolean'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'judul', 'deskripsi', 'link', 'urutan', 'gambar', 'existingGambar']);
        $this->aktif = true;
        $this->urutan = (int) (Ekatalog::max('urutan') ?? 0) + 1;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $e = Ekatalog::findOrFail($id);
        $this->editingId = $e->id;
        $this->judul = $e->judul;
        $this->deskripsi = (string) $e->deskripsi;
        $this->link = (string) $e->link;
        $this->urutan = $e->urutan;
        $this->aktif = $e->aktif;
        $this->existingGambar = $e->gambar;
        $this->gambar = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $payload = [
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi ?: null,
            'link' => $this->link ?: null,
            'urutan' => $this->urutan,
            'aktif' => $this->aktif,
        ];

        if ($this->gambar) {
            if ($this->existingGambar) {
                Storage::disk('public')->delete($this->existingGambar);
            }
            $payload['gambar'] = $this->gambar->store('ekatalog', 'public');
        }

        if ($this->editingId) {
            Ekatalog::findOrFail($this->editingId)->update($payload);
            $msg = 'E-Resources diperbarui.';
        } else {
            Ekatalog::create($payload);
            $msg = 'E-Resources ditambahkan.';
        }

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function toggle(int $id): void
    {
        $e = Ekatalog::findOrFail($id);
        $e->update(['aktif' => ! $e->aktif]);
        $this->dispatch('toast', type: 'success', message: 'Status diperbarui.');
    }

    public function delete(int $id): void
    {
        $e = Ekatalog::findOrFail($id);
        if ($e->gambar) {
            Storage::disk('public')->delete($e->gambar);
        }
        $e->delete();
        $this->dispatch('toast', type: 'success', message: 'E-Resources dihapus.');
    }

    public function with(): array
    {
        return ['list' => Ekatalog::orderBy('urutan')->orderBy('id')->get()];
    }
}; ?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
            <span class="font-semibold">E-Resources</span> tampil di halaman depan. Isi judul, deskripsi, tautan koleksi digital, & sampul.
        </div>
        <button wire:click="create" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
            <x-icon name="plus" class="h-4 w-4" /> Tambah E-Resources
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($list as $e)
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="relative aspect-[16/10] bg-emerald-50">
                    @if ($e->gambar)
                        <img src="{{ $e->gambarUrl() }}" alt="{{ $e->judul }}" class="h-full w-full object-cover" onerror="this.style.display='none'">
                    @else
                        <div class="grid h-full place-items-center text-emerald-200"><x-icon name="book" class="h-10 w-10" /></div>
                    @endif
                    <span class="absolute left-2 top-2"><x-badge :color="$e->aktif ? 'emerald' : 'zinc'">{{ $e->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></span>
                </div>
                <div class="p-3">
                    <p class="truncate font-semibold text-gray-800">{{ $e->judul }}</p>
                    <p class="line-clamp-2 text-xs text-gray-500">{{ $e->deskripsi }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button wire:click="toggle({{ $e->id }})" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50">{{ $e->aktif ? 'Nonaktif' : 'Aktif' }}</button>
                        <button wire:click="edit({{ $e->id }})" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50">Edit</button>
                        <button wire:click="delete({{ $e->id }})" wire:confirm="Hapus item ini?" class="rounded-md border border-rose-200 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border bg-white py-16 text-center text-gray-400">Belum ada E-Resources. Klik "Tambah E-Resources".</div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center">
            <div class="my-8 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} E-Resources</h3>
                    <button wire:click="$set('showForm', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Judul</label>
                        <input wire:model="judul" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('judul') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea wire:model="deskripsi" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        @error('deskripsi') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tautan (URL koleksi digital)</label>
                        <input wire:model="link" type="url" placeholder="https://..." class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('link') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sampul (gambar, opsional · 16:10)</label>
                        <input wire:model="gambar" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                        <div wire:loading wire:target="gambar" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                        @error('gambar') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <div class="mt-2">
                            @if ($gambar)
                                <img src="{{ $gambar->temporaryUrl() }}" class="aspect-[16/10] w-40 rounded-lg object-cover" />
                            @elseif ($existingGambar)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingGambar) }}" class="aspect-[16/10] w-40 rounded-lg object-cover" />
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Urutan</label>
                            <input wire:model="urutan" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                            @error('urutan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input wire:model="aktif" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" /> Tampilkan
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save,gambar" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
