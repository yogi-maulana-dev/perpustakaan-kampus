@props(['color' => 'zinc'])

@php
    $map = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        'zinc'    => 'bg-zinc-100 text-zinc-700 ring-zinc-600/20',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.($map[$color] ?? $map['zinc'])]) }}>
    {{ $slot }}
</span>
