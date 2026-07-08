<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $nama = '';
    public string $deskripsi = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('kelola master'), 403);
    }

    public function updatingSearch(): void { $this->resetPage(); }

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'nama', 'deskripsi']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $c = Category::findOrFail($id);
        $this->editingId = $c->id;
        $this->nama = $c->nama;
        $this->deskripsi = (string) $c->deskripsi;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        Category::updateOrCreate(['id' => $this->editingId], $data);
        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: 'Kategori disimpan.');
    }

    public function delete(int $id): void
    {
        $c = Category::withCount('books')->findOrFail($id);
        if ($c->books_count > 0) {
            $this->dispatch('toast', type: 'error', message: 'Tidak bisa dihapus, masih dipakai buku.');
            return;
        }
        $c->delete();
        $this->dispatch('toast', type: 'success', message: 'Kategori dihapus.');
    }

    public function with(): array
    {
        return [
            'items' => Category::withCount('books')
                ->when($this->search, fn ($q) => $q->whereLike('nama', "%{$this->search}%"))
                ->latest()->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="relative max-w-sm flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kategori…"
                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>
        <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            <x-icon name="plus" class="h-4 w-4" /> Tambah
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3 hidden md:table-cell">Deskripsi</th><th class="px-4 py-3">Buku</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($items as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $c->nama }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-gray-500">{{ \Illuminate\Support\Str::limit($c->deskripsi, 60) ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $c->books_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button wire:click="edit({{ $c->id }})" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Edit</button>
                                <button wire:click="delete({{ $c->id }})" wire:confirm="Hapus kategori ini?" class="rounded-md border border-rose-200 px-2.5 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} Kategori</h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input wire:model="nama" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('nama') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea wire:model="deskripsi" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
