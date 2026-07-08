<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $role = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $assignRole = '';
    public string $status = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(RoleName::managerRoles()), 403);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingRole(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'assignRole', 'status']);
        $this->status = 'active';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->password = '';
        $this->assignRole = $u->getRoleNames()->first() ?? '';
        $this->status = $u->status->value;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($this->editingId ? ','.$this->editingId : '')],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'assignRole' => ['required', 'in:'.implode(',', RoleName::staffRoles())],
            'status' => ['required', 'in:'.implode(',', array_column(UserStatus::cases(), 'value'))],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        if (! $this->editingId) {
            $payload['email_verified_at'] = now();
        }

        $user = User::updateOrCreate(['id' => $this->editingId], $payload);
        $user->syncRoles([$data['assignRole']]);

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: 'User disimpan.');
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('toast', type: 'error', message: 'Tidak bisa menghapus akun sendiri.');
            return;
        }
        User::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'User dihapus.');
    }

    public function with(): array
    {
        return [
            'users' => User::with('roles')
                ->whereHas('roles', fn ($r) => $r->whereIn('name', RoleName::staffRoles()))
                ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                    ->whereLike('name', "%{$this->search}%")
                    ->orWhereLike('email', "%{$this->search}%")))
                ->when($this->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->role)))
                ->latest()->paginate(10),
            'roles' => collect(RoleName::staffRoles()),
            'statuses' => UserStatus::cases(),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 gap-3">
            <div class="relative max-w-sm flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama / email…"
                       class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <select wire:model.live="role" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua Role</option>
                @foreach ($roles as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
            </select>
        </div>
        <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            <x-icon name="plus" class="h-4 w-4" /> Tambah User
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3 hidden md:table-cell">Email</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($users as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $u->name }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-gray-500">{{ $u->email }}</td>
                        <td class="px-4 py-3">@foreach ($u->roles as $r)<x-badge color="emerald">{{ $r->name }}</x-badge>@endforeach</td>
                        <td class="px-4 py-3"><x-badge :color="$u->status->color()">{{ $u->status->label() }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button wire:click="edit({{ $u->id }})" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Edit</button>
                                @if ($u->id !== auth()->id())
                                    <button wire:click="delete({{ $u->id }})" wire:confirm="Hapus user ini?" class="rounded-md border border-rose-200 px-2.5 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} User</h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input wire:model="name" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input wire:model="email" type="email" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                        <input wire:model="password" type="password" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select wire:model="assignRole" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— pilih —</option>
                                @foreach ($roles as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
                            </select>
                            @error('assignRole') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach ($statuses as $st) <option value="{{ $st->value }}">{{ $st->label() }}</option> @endforeach
                            </select>
                            @error('status') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
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
