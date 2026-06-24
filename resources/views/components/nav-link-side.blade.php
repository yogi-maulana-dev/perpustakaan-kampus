@props(['active' => false, 'icon' => 'dot'])

<a {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg px-3 py-2 transition '.
    ($active
        ? 'bg-emerald-600 text-white font-medium'
        : 'text-emerald-100 hover:bg-white/10 hover:text-white')]) }}>
    <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
