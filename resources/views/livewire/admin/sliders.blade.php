<?php

use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $judul = '';
    public string $subjudul = '';
    public int $urutan = 0;
    public bool $aktif = true;
    public $gambar = null;
    public ?string $existingGambar = null;
    public $gambar_mobile = null;
    public ?string $existingGambarMobile = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('kelola master'), 403);
    }

    protected function rules(): array
    {
        return [
            'judul' => ['nullable', 'string', 'max:255'],
            'subjudul' => ['nullable', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0'],
            'aktif' => ['boolean'],
            // Rasio landscape lebar; disarankan 1600 x 600 px.
            'gambar' => [$this->editingId ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gambar_mobile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'judul', 'subjudul', 'urutan', 'gambar', 'existingGambar', 'gambar_mobile', 'existingGambarMobile']);
        $this->aktif = true;
        $this->urutan = (int) (Slider::max('urutan') ?? 0) + 1;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $s = Slider::findOrFail($id);
        $this->editingId = $s->id;
        $this->judul = (string) $s->judul;
        $this->subjudul = (string) $s->subjudul;
        $this->urutan = $s->urutan;
        $this->aktif = $s->aktif;
        $this->existingGambar = $s->gambar;
        $this->gambar = null;
        $this->existingGambarMobile = $s->gambar_mobile;
        $this->gambar_mobile = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'judul' => $this->judul ?: null,
            'subjudul' => $this->subjudul ?: null,
            'urutan' => $this->urutan,
            'aktif' => $this->aktif,
        ];

        if ($this->gambar) {
            if ($this->existingGambar) {
                Storage::disk('public')->delete($this->existingGambar);
            }
            $payload['gambar'] = $this->gambar->store('sliders', 'public');
        }

        if ($this->gambar_mobile) {
            if ($this->existingGambarMobile) {
                Storage::disk('public')->delete($this->existingGambarMobile);
            }
            $payload['gambar_mobile'] = $this->gambar_mobile->store('sliders', 'public');
        }

        if ($this->editingId) {
            Slider::findOrFail($this->editingId)->update($payload);
            $msg = 'Slider diperbarui.';
        } else {
            Slider::create($payload);
            $msg = 'Slider ditambahkan.';
        }

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function toggle(int $id): void
    {
        $s = Slider::findOrFail($id);
        $s->update(['aktif' => ! $s->aktif]);
        $this->dispatch('toast', type: 'success', message: 'Status slider diperbarui.');
    }

    public function delete(int $id): void
    {
        $s = Slider::findOrFail($id);
        if ($s->gambar) {
            Storage::disk('public')->delete($s->gambar);
        }
        if ($s->gambar_mobile) {
            Storage::disk('public')->delete($s->gambar_mobile);
        }
        $s->delete();
        $this->dispatch('toast', type: 'success', message: 'Slider dihapus.');
    }

    public function with(): array
    {
        return ['sliders' => Slider::orderBy('urutan')->orderByDesc('id')->get()];
    }
}; ?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
            <span class="font-semibold">Ketentuan gambar:</span>
            Desktop <strong>1600 × 600 px</strong> (landscape) · Mobile/iPhone SE <strong>720 × 1080 px</strong> (potret ~2:3) ·
            JPG/PNG/WEBP · maks <strong>2 MB</strong>.
        </div>
        <button wire:click="create" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
            <x-icon name="plus" class="h-4 w-4" /> Tambah Slider
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse ($sliders as $s)
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="relative aspect-[8/3] bg-gray-100">
                    <img src="{{ $s->gambarUrl() }}" alt="{{ $s->judul }}" class="h-full w-full object-cover" onerror="this.style.display='none'">
                    <span class="absolute left-2 top-2">
                        <x-badge :color="$s->aktif ? 'emerald' : 'zinc'">{{ $s->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </span>
                    <span class="absolute right-2 top-2 rounded bg-black/50 px-2 py-0.5 text-xs text-white">#{{ $s->urutan }}</span>
                </div>
                <div class="flex items-center justify-between p-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800">{{ $s->judul ?: '(tanpa judul)' }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $s->subjudul }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button wire:click="toggle({{ $s->id }})" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">{{ $s->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button wire:click="edit({{ $s->id }})" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Edit</button>
                        <button wire:click="delete({{ $s->id }})" wire:confirm="Hapus slider ini?" class="rounded-md border border-rose-200 px-2.5 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border bg-white py-16 text-center text-gray-400">
                Belum ada slider. Klik "Tambah Slider" untuk mengunggah.
            </div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center">
            <div class="my-8 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} Slider</h3>
                    <button wire:click="$set('showForm', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gambar (1600 × 600 px · JPG/PNG/WEBP · maks 2MB)</label>
                        <input wire:model="gambar" type="file" accept=".jpg,.jpeg,.png,.webp"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                        <div wire:loading wire:target="gambar" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                        @error('gambar') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror

                        <div class="mt-2">
                            @if ($gambar)
                                <img src="{{ $gambar->temporaryUrl() }}" class="aspect-[8/3] w-full rounded-lg object-cover" />
                            @elseif ($existingGambar)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingGambar) }}" class="aspect-[8/3] w-full rounded-lg object-cover" />
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gambar Mobile / iPhone SE (720 × 1080 px, potret · opsional)</label>
                        <input wire:model="gambar_mobile" type="file" accept=".jpg,.jpeg,.png,.webp"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                        <div wire:loading wire:target="gambar_mobile" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                        @error('gambar_mobile') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <p class="mt-1 text-xs text-gray-400">Khusus tampilan HP. Kosongkan untuk memakai gambar desktop.</p>
                        <div class="mt-2">
                            @if ($gambar_mobile)
                                <img src="{{ $gambar_mobile->temporaryUrl() }}" class="h-48 w-auto rounded-lg object-cover" />
                            @elseif ($existingGambarMobile)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingGambarMobile) }}" class="h-48 w-auto rounded-lg object-cover" />
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Judul (opsional)</label>
                        <input wire:model="judul" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subjudul (opsional)</label>
                        <input wire:model="subjudul" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Urutan</label>
                            <input wire:model="urutan" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                            @error('urutan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input wire:model="aktif" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                Tampilkan (aktif)
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
