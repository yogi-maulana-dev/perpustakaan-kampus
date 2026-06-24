@props(['label' => '', 'value' => '', 'icon' => 'dot', 'color' => 'indigo'])

@php
    $map = [
        'indigo'  => 'bg-indigo-50 text-indigo-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'rose'    => 'bg-rose-50 text-rose-600',
        'sky'     => 'bg-sky-50 text-sky-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl border bg-white p-5 shadow-sm']) }}>
    <div class="grid h-12 w-12 place-items-center rounded-lg {{ $map[$color] ?? $map['indigo'] }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <div>
        <p class="text-sm text-gray-500">{{ $label }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ $value }}</p>
    </div>
</div>
