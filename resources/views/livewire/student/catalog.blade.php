<?php

use App\Actions\Loans\SubmitLoanRequest;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('pinjam buku'), 403);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }

    public function pinjam(int $bookId, SubmitLoanRequest $action): void
    {
        $book = Book::findOrFail($bookId);

        try {
            $action->handle(auth()->user(), $book);
            $this->dispatch('toast', type: 'success', message: 'Pengajuan peminjaman terkirim. Menunggu persetujuan pustakawan.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function with(): array
    {
        return [
            'books' => Book::with(['category', 'author'])
                ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                    ->where('judul', 'ilike', "%{$this->search}%")
                    ->orWhere('isbn', 'ilike', "%{$this->search}%")
                    ->orWhereHas('author', fn ($a) => $a->where('nama', 'ilike', "%{$this->search}%"))))
                ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
                ->orderBy('judul')
                ->paginate(12),
            'categories' => Category::orderBy('nama')->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <div class="relative max-w-md flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari judul / penulis / ISBN…"
                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>
        <select wire:model.live="category" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}">{{ $c->nama }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @forelse ($books as $book)
            <div class="flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="aspect-[3/4] bg-gray-100">
                    <img src="{{ $book->cover_url }}" alt="{{ $book->judul }}" class="h-full w-full object-cover" />
                </div>
                <div class="flex flex-1 flex-col p-3">
                    <h3 class="line-clamp-2 text-sm font-semibold text-gray-800">{{ $book->judul }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $book->author->nama }}</p>
                    <div class="mt-2">
                        <x-badge :color="$book->stok_tersedia > 0 ? 'emerald' : 'rose'">
                            {{ $book->stok_tersedia > 0 ? 'Tersedia ('.$book->stok_tersedia.')' : 'Habis' }}
                        </x-badge>
                    </div>
                    <button wire:click="pinjam({{ $book->id }})" wire:confirm="Ajukan peminjaman buku ini?"
                            @disabled($book->stok_tersedia < 1)
                            class="mt-3 w-full rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        Ajukan Pinjam
                    </button>

                    @php
                        $waUrl = \App\Models\Setting::waUrl([
                            'nama' => auth()->user()->name,
                            'identitas' => auth()->user()->mahasiswaProfile?->nomorIdentitas() ?? '-',
                            'judul' => $book->judul,
                            'kode' => $book->kode_buku,
                        ]);
                    @endphp
                    @if ($waUrl)
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                           class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 1.67c2.2 0 4.27.86 5.83 2.42a8.2 8.2 0 012.41 5.82c0 4.54-3.7 8.24-8.25 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.18 8.18 0 01-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24zm4.52 10.32c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.13-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"/></svg>
                            Pinjam via WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border bg-white py-16 text-center text-gray-400">Tidak ada buku ditemukan.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
</div>
