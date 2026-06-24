<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    // Form fields
    public string $kode_buku = '';
    public string $isbn = '';
    public string $judul = '';
    public ?int $category_id = null;
    public ?int $author_id = null;
    public ?int $publisher_id = null;
    public ?int $shelf_id = null;
    public ?int $tahun_terbit = null;
    public int $jumlah_stok = 1;
    public string $deskripsi = '';
    public $cover = null;
    public ?string $existingCover = null;

    public bool $canManage = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat buku'), 403);
        $this->canManage = auth()->user()->can('kelola buku');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }

    protected function rules(): array
    {
        $unique = 'unique:books,kode_buku'.($this->editingId ? ','.$this->editingId : '');

        return [
            'kode_buku' => ['required', 'string', 'max:50', $unique],
            'isbn' => ['nullable', 'string', 'max:30'],
            'judul' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'author_id' => ['required', 'exists:authors,id'],
            'publisher_id' => ['required', 'exists:publishers,id'],
            'shelf_id' => ['nullable', 'exists:shelves,id'],
            'tahun_terbit' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'jumlah_stok' => ['required', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeManage();
        $book = Book::findOrFail($id);
        $this->editingId = $book->id;
        $this->fill($book->only([
            'kode_buku', 'isbn', 'judul', 'category_id', 'author_id',
            'publisher_id', 'shelf_id', 'tahun_terbit', 'jumlah_stok', 'deskripsi',
        ]));
        $this->existingCover = $book->cover;
        $this->cover = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();
        $validated = $this->validate();

        $payload = collect($validated)->except('cover')->all();

        if ($this->cover) {
            if ($this->existingCover) {
                Storage::disk('public')->delete($this->existingCover);
            }
            $payload['cover'] = $this->cover->store('covers', 'public');
        }

        if ($this->editingId) {
            $book = Book::findOrFail($this->editingId);
            // Jaga konsistensi stok tersedia saat jumlah_stok berubah.
            $selisih = $payload['jumlah_stok'] - $book->jumlah_stok;
            $payload['stok_tersedia'] = max(0, $book->stok_tersedia + $selisih);
            $book->update($payload);
            $msg = 'Buku diperbarui.';
        } else {
            $payload['stok_tersedia'] = $payload['jumlah_stok'];
            Book::create($payload);
            $msg = 'Buku ditambahkan.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function delete(int $id): void
    {
        $this->authorizeManage();
        $book = Book::findOrFail($id);
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        $book->delete();
        $this->dispatch('toast', type: 'success', message: 'Buku dihapus.');
    }

    private function authorizeManage(): void
    {
        abort_unless($this->canManage, 403);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'kode_buku', 'isbn', 'judul', 'category_id', 'author_id',
            'publisher_id', 'shelf_id', 'tahun_terbit', 'deskripsi', 'cover', 'existingCover',
        ]);
        $this->jumlah_stok = 1;
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'books' => Book::with(['category', 'author', 'publisher'])
                ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                    ->where('judul', 'ilike', "%{$this->search}%")
                    ->orWhere('kode_buku', 'ilike', "%{$this->search}%")
                    ->orWhere('isbn', 'ilike', "%{$this->search}%")))
                ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
                ->latest()
                ->paginate(8),
            'categories' => Category::orderBy('nama')->get(),
            'authors' => Author::orderBy('nama')->get(),
            'publishers' => Publisher::orderBy('nama')->get(),
            'shelves' => Shelf::orderBy('kode_rak')->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <div class="relative max-w-sm flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari judul / kode / ISBN…"
                       class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <select wire:model.live="category" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nama }}</option>
                @endforeach
            </select>
        </div>
        @if ($canManage)
            <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <x-icon name="plus" class="h-4 w-4" /> Tambah Buku
            </button>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($books as $book)
            <div class="flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="aspect-[3/4] bg-gray-100">
                    <img src="{{ $book->cover_url }}" alt="{{ $book->judul }}" class="h-full w-full object-cover" />
                </div>
                <div class="flex flex-1 flex-col p-3">
                    <p class="text-xs text-gray-400">{{ $book->kode_buku }}</p>
                    <h3 class="line-clamp-2 font-semibold text-gray-800">{{ $book->judul }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $book->author->nama }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <x-badge color="emerald">{{ $book->category->nama }}</x-badge>
                        <x-badge :color="$book->stok_tersedia > 0 ? 'emerald' : 'rose'">Stok {{ $book->stok_tersedia }}/{{ $book->jumlah_stok }}</x-badge>
                    </div>
                    @if ($canManage)
                        <div class="mt-3 flex gap-2 border-t pt-3">
                            <button wire:click="edit({{ $book->id }})" class="flex-1 rounded-md border px-2 py-1 text-xs hover:bg-gray-50">Edit</button>
                            <button wire:click="delete({{ $book->id }})" wire:confirm="Hapus buku ini?" class="rounded-md border border-rose-200 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border bg-white py-16 text-center text-gray-400">Tidak ada buku.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $books->links() }}</div>

    {{-- Form modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit Buku' : 'Tambah Buku' }}</h3>
                    <button wire:click="$set('showForm', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode Buku</label>
                        <input wire:model="kode_buku" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('kode_buku') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ISBN</label>
                        <input wire:model="isbn" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('isbn') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Judul</label>
                        <input wire:model="judul" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('judul') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select wire:model="category_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($categories as $c) <option value="{{ $c->id }}">{{ $c->nama }}</option> @endforeach
                        </select>
                        @error('category_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Penulis</label>
                        <select wire:model="author_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($authors as $a) <option value="{{ $a->id }}">{{ $a->nama }}</option> @endforeach
                        </select>
                        @error('author_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Penerbit</label>
                        <select wire:model="publisher_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($publishers as $p) <option value="{{ $p->id }}">{{ $p->nama }}</option> @endforeach
                        </select>
                        @error('publisher_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rak</label>
                        <select wire:model="shelf_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($shelves as $sh) <option value="{{ $sh->id }}">{{ $sh->kode_rak }} ({{ $sh->lokasi }})</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun Terbit</label>
                        <input wire:model="tahun_terbit" type="number" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('tahun_terbit') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Stok</label>
                        <input wire:model="jumlah_stok" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('jumlah_stok') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Cover</label>
                        <input wire:model="cover" type="file" accept="image/*" class="mt-1 w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:text-emerald-700" />
                        <div wire:loading wire:target="cover" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                        @error('cover') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <div class="mt-2 flex gap-2">
                            @if ($cover)
                                <img src="{{ $cover->temporaryUrl() }}" class="h-24 w-[72px] rounded object-cover" />
                            @elseif ($existingCover)
                                <img src="{{ Storage::disk('public')->url($existingCover) }}" class="h-24 w-[72px] rounded object-cover" />
                            @else
                                <img src="{{ asset('images/no-cover.svg') }}" class="h-24 w-[72px] rounded border object-cover" />
                            @endif
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea wire:model="deskripsi" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-2 border-t pt-4">
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save,cover" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
