<?php

use App\Models\Pengurus;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $nama = '';
    public string $jabatan = '';
    public int $urutan = 0;
    public bool $aktif = true;
    public $foto = null;
    public ?string $existingFoto = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('kelola master'), 403);
    }

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0'],
            'aktif' => ['boolean'],
            'foto' => [$this->editingId ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'nama', 'jabatan', 'urutan', 'foto', 'existingFoto']);
        $this->aktif = true;
        $this->urutan = (int) (Pengurus::max('urutan') ?? 0) + 1;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $p = Pengurus::findOrFail($id);
        $this->editingId = $p->id;
        $this->nama = $p->nama;
        $this->jabatan = $p->jabatan;
        $this->urutan = $p->urutan;
        $this->aktif = $p->aktif;
        $this->existingFoto = $p->foto;
        $this->foto = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $payload = [
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'urutan' => $this->urutan,
            'aktif' => $this->aktif,
        ];

        if ($this->foto) {
            if ($this->existingFoto) {
                Storage::disk('public')->delete($this->existingFoto);
            }
            $payload['foto'] = $this->foto->store('pengurus', 'public');
        }

        if ($this->editingId) {
            Pengurus::findOrFail($this->editingId)->update($payload);
            $msg = 'Pengurus diperbarui.';
        } else {
            Pengurus::create($payload);
            $msg = 'Pengurus ditambahkan.';
        }

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function toggle(int $id): void
    {
        $p = Pengurus::findOrFail($id);
        $p->update(['aktif' => ! $p->aktif]);
        $this->dispatch('toast', type: 'success', message: 'Status diperbarui.');
    }

    public function delete(int $id): void
    {
        $p = Pengurus::findOrFail($id);
        if ($p->foto) {
            Storage::disk('public')->delete($p->foto);
        }
        $p->delete();
        $this->dispatch('toast', type: 'success', message: 'Pengurus dihapus.');
    }

    public function with(): array
    {
        return ['list' => Pengurus::orderBy('urutan')->orderBy('id')->get()];
    }
}; ?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
            <span class="font-semibold">Ketentuan foto:</span> potret/persegi disarankan <strong>600 × 750 px</strong> ·
            format <strong>JPG, PNG, WEBP</strong> · maks <strong>2 MB</strong>.
        </div>
        <button wire:click="create" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
            <x-icon name="plus" class="h-4 w-4" /> Tambah Pengurus
        </button>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($list as $p)
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="relative aspect-[4/5] bg-emerald-50">
                    @if ($p->foto)
                        <img src="{{ $p->fotoUrl() }}" alt="{{ $p->nama }}" class="h-full w-full object-cover" onerror="this.style.display='none'">
                    @else
                        <div class="grid h-full place-items-center text-3xl font-bold text-emerald-300">{{ strtoupper(substr($p->nama, 0, 1)) }}</div>
                    @endif
                    <span class="absolute left-2 top-2"><x-badge :color="$p->aktif ? 'emerald' : 'zinc'">{{ $p->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></span>
                    <span class="absolute right-2 top-2 rounded bg-black/50 px-2 py-0.5 text-xs text-white">#{{ $p->urutan }}</span>
                </div>
                <div class="p-3">
                    <p class="truncate font-semibold text-gray-800">{{ $p->nama }}</p>
                    <p class="truncate text-xs text-emerald-700">{{ $p->jabatan }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button wire:click="toggle({{ $p->id }})" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50">{{ $p->aktif ? 'Nonaktif' : 'Aktif' }}</button>
                        <button wire:click="edit({{ $p->id }})" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50">Edit</button>
                        <button wire:click="delete({{ $p->id }})" wire:confirm="Hapus pengurus ini?" class="rounded-md border border-rose-200 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border bg-white py-16 text-center text-gray-400">Belum ada pengurus. Klik "Tambah Pengurus".</div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center">
            <div class="my-8 w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} Pengurus</h3>
                    <button wire:click="$set('showForm', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto (600 × 750 px · JPG/PNG/WEBP · maks 2MB)</label>
                        <input wire:model="foto" type="file" accept=".jpg,.jpeg,.png,.webp"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                        <div wire:loading wire:target="foto" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                        @error('foto') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <div class="mt-2">
                            @if ($foto)
                                <img src="{{ $foto->temporaryUrl() }}" class="h-32 w-28 rounded-lg object-cover" />
                            @elseif ($existingFoto)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingFoto) }}" class="h-32 w-28 rounded-lg object-cover" />
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input wire:model="nama" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('nama') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                        <input wire:model="jabatan" type="text" placeholder="mis. Kepala Perpustakaan" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('jabatan') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
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
                        <button type="submit" wire:loading.attr="disabled" wire:target="save,foto" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
