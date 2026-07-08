<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.dashboard')] class extends Component {
    public string $from = '';
    public string $to = '';

    public bool $canExport = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat laporan'), 403);
        $this->canExport = auth()->user()->can('export laporan');
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function query(): string
    {
        return http_build_query(array_filter(['from' => $this->from, 'to' => $this->to]));
    }
}; ?>

<div>
    @if ($canExport)
        <div class="mb-4 rounded-xl border bg-white p-4 shadow-sm">
            <p class="mb-2 text-sm font-medium text-gray-700">Filter periode (untuk laporan peminjaman & denda)</p>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500">Dari</label>
                    <input wire:model="from" type="date" class="mt-1 rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Sampai</label>
                    <input wire:model="to" type="date" class="mt-1 rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @php
            $cards = [
                ['type' => 'books', 'label' => 'Laporan Data Buku', 'icon' => 'book', 'excel' => true],
                ['type' => 'students', 'label' => 'Laporan Data Anggota', 'icon' => 'users', 'excel' => false],
                ['type' => 'loans', 'label' => 'Laporan Peminjaman', 'icon' => 'swap', 'excel' => 'transactions'],
                ['type' => 'fines', 'label' => 'Laporan Denda', 'icon' => 'cash', 'excel' => false],
                ['type' => 'renewals', 'label' => 'Laporan Perpanjangan Kartu', 'icon' => 'clock', 'excel' => true],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                        <x-icon :name="$card['icon']" class="h-5 w-5" />
                    </div>
                    <h3 class="font-semibold text-gray-800">{{ $card['label'] }}</h3>
                </div>
                @if ($canExport)
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('reports.pdf', $card['type']) }}?{{ $this->query() }}"
                           class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700">
                            <x-icon name="chart" class="h-4 w-4" /> PDF
                        </a>
                        @if ($card['excel'])
                            <a href="{{ route('reports.excel', $card['excel'] === true ? $card['type'] : $card['excel']) }}?{{ $this->query() }}"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                <x-icon name="grid" class="h-4 w-4" /> Excel
                            </a>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-xs text-gray-400">Anda tidak memiliki izin export.</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
