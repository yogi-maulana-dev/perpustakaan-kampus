<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    #[Computed]
    public function items()
    {
        return auth()->user()->notifications()->latest()->limit(8)->get();
    }

    #[Computed]
    public function unread(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }
}; ?>

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100">
        <x-icon name="bell" class="h-6 w-6" />
        @if ($this->unread > 0)
            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $this->unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         class="absolute right-0 mt-2 w-80 max-w-[90vw] rounded-lg border bg-white shadow-lg">
        <div class="flex items-center justify-between border-b px-4 py-2.5">
            <p class="text-sm font-semibold text-gray-700">Notifikasi</p>
            @if ($this->unread > 0)
                <button wire:click="markAllRead" class="text-xs text-emerald-600 hover:underline">Tandai semua dibaca</button>
            @endif
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($this->items as $n)
                <button wire:click="markRead('{{ $n->id }}')"
                        class="flex w-full items-start gap-3 border-b px-4 py-3 text-left hover:bg-gray-50 {{ $n->read_at ? 'opacity-60' : '' }}">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-gray-300' : 'bg-emerald-500' }}"></span>
                    <span>
                        <span class="block text-sm font-medium text-gray-800">{{ $n->data['title'] ?? 'Notifikasi' }}</span>
                        <span class="block text-xs text-gray-500">{{ $n->data['message'] ?? '' }}</span>
                        <span class="mt-0.5 block text-[11px] text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-400">Belum ada notifikasi.</p>
            @endforelse
        </div>
    </div>
</div>
