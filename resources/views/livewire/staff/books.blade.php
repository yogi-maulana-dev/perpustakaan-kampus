<?php

use App\Exports\BooksTemplateExport;
use App\Imports\BooksImport;
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
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sort = 'terbaru';

    public bool $showForm = false;
    public ?int $editingId = null;

    // Form fields
    public string $kode_buku = '';
    public ?string $isbn = '';
    public string $judul = '';
    public ?int $category_id = null;
    public ?int $author_id = null;
    public ?int $publisher_id = null;
    public ?int $shelf_id = null;
    public ?int $tahun_terbit = null;
    public ?string $cetakan = '';
    public int $jumlah_stok = 1;
    public ?string $deskripsi = '';
    public $cover = null;
    public ?string $existingCover = null;
    public ?string $judulWarning = null;
    public ?string $isbnWarning = null;

    public bool $canManage = false;

    // Import Excel & upload cover massal
    public bool $showImport = false;
    public $importFile = null;
    public bool $showCovers = false;
    public array $covers = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat buku'), 403);
        $this->canManage = auth()->user()->can('kelola buku');
    }

    /** Peringatan bila judul buku sudah ada (deteksi duplikat, tidak memblokir). */
    public function updatedJudul(): void
    {
        $judul = trim($this->judul);
        $this->judulWarning = null;

        if (mb_strlen($judul) < 3) {
            return;
        }

        $count = Book::whereRaw('lower(judul) = ?', [mb_strtolower($judul)])
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->count();

        if ($count > 0) {
            $this->judulWarning = "Judul ini sudah ada di {$count} data buku. Pastikan bukan duplikat.";
        }
    }

    /** Peringatan bila ISBN sudah dipakai buku lain (tidak memblokir). */
    public function updatedIsbn(): void
    {
        $isbn = trim((string) $this->isbn);
        $this->isbnWarning = null;

        if ($isbn === '') {
            return;
        }

        $count = Book::whereRaw('lower(isbn) = ?', [mb_strtolower($isbn)])
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->count();

        if ($count > 0) {
            $this->isbnWarning = "ISBN ini sudah terdaftar pada {$count} data buku.";
        }
    }

    public function downloadTemplate()
    {
        $this->authorizeManage();

        return Excel::download(new BooksTemplateExport, 'template-import-buku.xlsx');
    }

    public function importExcel(): void
    {
        $this->authorizeManage();
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [], ['importFile' => 'berkas Excel']);

        $import = new BooksImport;
        Excel::import($import, $this->importFile); // format (.xlsx/.xls/.csv) terdeteksi otomatis dari ekstensi

        $this->reset('importFile', 'showImport');
        $this->resetPage();
        $this->dispatch('toast', type: 'success',
            message: "Import selesai: {$import->imported} buku ditambahkan".($import->skipped ? ", {$import->skipped} baris dilewati (tanpa judul)." : '.'));
    }

    public function uploadCovers(): void
    {
        $this->authorizeManage();
        $this->validate([
            'covers.*' => ['image', 'max:2048'],
        ], [], ['covers.*' => 'gambar']);

        $matched = 0;
        $unmatched = [];

        foreach ($this->covers as $file) {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $book = Book::whereRaw('lower(kode_buku) = ?', [strtolower(trim($name))])->first();

            if (! $book) {
                $unmatched[] = $name;
                continue;
            }

            if ($book->cover) {
                Storage::disk('public')->delete($book->cover);
            }
            $book->update(['cover' => $file->store('covers', 'public')]);
            $matched++;
        }

        $this->reset('covers', 'showCovers');
        $msg = "{$matched} cover terpasang.";
        if ($unmatched) {
            $msg .= ' Tidak cocok (kode buku tak ditemukan): '.implode(', ', array_slice($unmatched, 0, 5)).(count($unmatched) > 5 ? ', …' : '');
        }
        $this->dispatch('toast', type: $matched ? 'success' : 'warning', message: $msg);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }
    public function updatingSort(): void { $this->resetPage(); }

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
            'cetakan' => ['nullable', 'string', 'max:50'],
            'jumlah_stok' => ['required', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->kode_buku = $this->nextKodeBuku();
        $this->showForm = true;
    }

    /** Kode buku berikutnya secara berurutan (BK-00001, BK-00002, …). */
    public function nextKodeBuku(): string
    {
        $max = Book::where('kode_buku', 'like', 'BK-%')
            ->pluck('kode_buku')
            ->map(fn ($k) => (int) preg_replace('/\D/', '', $k))
            ->max() ?? 0;

        $n = $max + 1;
        do {
            $kode = 'BK-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
            $n++;
        } while (Book::where('kode_buku', $kode)->exists());

        return $kode;
    }

    /** Tombol: ambil ulang kode berurutan terbaru (mis. bila ada bentrok saat input bersamaan). */
    public function refreshKode(): void
    {
        $this->authorizeManage();
        $this->kode_buku = $this->nextKodeBuku();
    }

    public function edit(int $id): void
    {
        $this->authorizeManage();
        $book = Book::findOrFail($id);
        $this->editingId = $book->id;
        $this->fill($book->only([
            'kode_buku', 'isbn', 'judul', 'category_id', 'author_id',
            'publisher_id', 'shelf_id', 'tahun_terbit', 'cetakan', 'jumlah_stok', 'deskripsi',
        ]));
        $this->existingCover = $book->cover;
        $this->cover = null;
        $this->judulWarning = null;
        $this->isbnWarning = null;
        $this->updatedJudul();
        $this->updatedIsbn();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();

        // Input bersamaan: bila kode berurutan sudah keburu dipakai staf lain, ambil nomor berikutnya.
        if (! $this->editingId && Book::where('kode_buku', $this->kode_buku)->exists()) {
            $this->kode_buku = $this->nextKodeBuku();
        }

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
            'publisher_id', 'shelf_id', 'tahun_terbit', 'cetakan', 'deskripsi', 'cover', 'existingCover', 'judulWarning', 'isbnWarning',
        ]);
        $this->jumlah_stok = 1;
        $this->resetValidation();
    }

    public function with(): array
    {
        // Tipe cast ke teks berbeda antar-DB (pgsql: text, mysql: char).
        $txt = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'text' : 'char';

        $query = Book::with(['category', 'author', 'publisher'])
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->whereLike('judul', "%{$this->search}%")
                ->orWhereLike('kode_buku', "%{$this->search}%")
                ->orWhereLike('isbn', "%{$this->search}%")
                // cari juga berdasar tahun terbit & tanggal input (tanggal/bulan/tahun)
                ->orWhereRaw("cast(tahun_terbit as {$txt}) like ?", ["%{$this->search}%"])
                ->orWhereRaw("cast(created_at as {$txt}) like ?", ["%{$this->search}%"])))
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category));

        match ($this->sort) {
            'terlama' => $query->oldest(),
            'judul_az' => $query->orderBy('judul'),
            'judul_za' => $query->orderByDesc('judul'),
            'tahun_baru' => $query->orderByDesc('tahun_terbit')->orderByDesc('id'),
            'tahun_lama' => $query->orderBy('tahun_terbit')->orderBy('id'),
            'kode' => $query->orderBy('kode_buku'),
            default => $query->latest(),
        };

        return [
            'books' => $query->paginate(8),
            'categories' => Category::orderBy('nama')->get(),
            'authors' => Author::orderBy('nama')->get(),
            'publishers' => Publisher::orderBy('nama')->get(),
            'shelves' => Shelf::orderBy('kode_rak')->get(),
        ];
    }
}; ?>

{{-- Realtime: perbarui daftar tiap 20 dtk saat tidak sedang mengisi form (agar tak mengganggu input). --}}
<div @if (! $showForm && ! $showImport && ! $showCovers) wire:poll.20s @endif>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <div class="relative max-w-sm flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari judul / kode / ISBN / tahun…"
                       class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <select wire:model.live="category" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nama }}</option>
                @endforeach
            </select>
            <select wire:model.live="sort" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" title="Urutkan">
                <option value="terbaru">Terbaru ditambahkan</option>
                <option value="terlama">Terlama ditambahkan</option>
                <option value="judul_az">Judul A → Z</option>
                <option value="judul_za">Judul Z → A</option>
                <option value="tahun_baru">Tahun terbit terbaru</option>
                <option value="tahun_lama">Tahun terbit terlama</option>
                <option value="kode">Kode buku (urut)</option>
            </select>
        </div>
        @if ($canManage)
            <div class="flex flex-wrap gap-2">
                <button wire:click="$set('showImport', true)" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                    <x-icon name="download" class="h-4 w-4 rotate-180" /> Import Excel
                </button>
                <button wire:click="$set('showCovers', true)" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                    <x-icon name="image" class="h-4 w-4" /> Cover Massal
                </button>
                <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    <x-icon name="plus" class="h-4 w-4" /> Tambah Buku
                </button>
            </div>
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
                        <div class="mt-1 flex gap-2">
                            <input wire:model="kode_buku" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                            @unless ($editingId)
                                <button type="button" wire:click="refreshKode" title="Ambil nomor urut terbaru"
                                        class="grid w-10 shrink-0 place-items-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                </button>
                            @endunless
                        </div>
                        @unless ($editingId)
                            <p class="mt-1 text-xs text-gray-400">Terisi otomatis berurutan. Klik ikon untuk memperbarui bila bentrok.</p>
                        @endunless
                        @error('kode_buku') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ISBN</label>
                        <input wire:model.live.debounce.500ms="isbn" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('isbn') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        @if ($isbnWarning)
                            <p class="mt-1 flex items-center gap-1 text-xs text-amber-600">
                                <x-icon name="x-circle" class="h-4 w-4" /> {{ $isbnWarning }}
                            </p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Judul</label>
                        <input wire:model.live.debounce.500ms="judul" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('judul') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        @if ($judulWarning)
                            <p class="mt-1 flex items-center gap-1 text-xs text-amber-600">
                                <x-icon name="x-circle" class="h-4 w-4" /> {{ $judulWarning }}
                            </p>
                        @endif
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
                        <label class="block text-sm font-medium text-gray-700">Cet/ED <span class="text-gray-400">(Cetakan/Edisi)</span></label>
                        <input wire:model="cetakan" type="text" placeholder="mis. 2" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('cetakan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Stok</label>
                        <input wire:model="jumlah_stok" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('jumlah_stok') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Cover Buku</label>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Rasio <strong>3:4 (potret)</strong> — disarankan <strong>600 × 800 px</strong> (atau kelipatannya, mis. 900×1200 px).
                            Format JPG/PNG/WEBP, maksimal 2 MB.
                        </p>
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

    {{-- Import Excel modal --}}
    @if ($showImport)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4">
            <div class="my-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Import Buku dari Excel</h3>
                    <button wire:click="$set('showImport', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <div class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                    <p class="font-medium">Langkah:</p>
                    <ol class="mt-1 list-inside list-decimal space-y-0.5 text-emerald-700">
                        <li>Unduh template, isi datanya (kolom <strong>Judul Buku</strong> wajib).</li>
                        <li>Kolom: Nama Pengarang, Penerbit, Tahun Terbit, Cet/ED, Kategori, ISBN, Stok — opsional.</li>
                        <li>Penulis/penerbit/kategori dibuat otomatis bila belum ada. Kode buku otomatis bila kosong.</li>
                    </ol>
                    <button wire:click="downloadTemplate" class="mt-2 inline-flex items-center gap-1 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-300 hover:bg-emerald-100">
                        <x-icon name="download" class="h-4 w-4" /> Unduh Template (.xlsx)
                    </button>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Berkas Excel (.xlsx / .xls 2003 / .csv)</label>
                    <input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv"
                           class="mt-1 w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:text-emerald-700" />
                    <div wire:loading wire:target="importFile" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                    @error('importFile') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t pt-4">
                    <button wire:click="$set('showImport', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="importExcel" wire:loading.attr="disabled" wire:target="importExcel,importFile"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                        <span wire:loading wire:target="importExcel" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Proses Import
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Upload cover massal modal --}}
    @if ($showCovers)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4">
            <div class="my-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Upload Cover Massal</h3>
                    <button wire:click="$set('showCovers', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    Beri nama tiap file gambar <strong>sama dengan Kode Buku</strong>-nya, contoh: <code class="rounded bg-white px-1">BK-00001.jpg</code>.
                    Sistem mencocokkan otomatis. Rasio 3:4, maks 2&nbsp;MB per gambar.
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Pilih banyak gambar sekaligus</label>
                    <input wire:model="covers" type="file" accept="image/*" multiple
                           class="mt-1 w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:text-emerald-700" />
                    <div wire:loading wire:target="covers" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                    @error('covers.*') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    @if (count($covers))
                        <p class="mt-2 text-xs text-gray-500">{{ count($covers) }} berkas siap diproses.</p>
                    @endif
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t pt-4">
                    <button wire:click="$set('showCovers', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="uploadCovers" wire:loading.attr="disabled" wire:target="uploadCovers,covers"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                        <span wire:loading wire:target="uploadCovers" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Pasang Cover
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
