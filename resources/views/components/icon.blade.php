@props(['name' => 'dot'])

@php
    $paths = [
        'home'       => '<path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"/>',
        'book'       => '<path d="M4 5a2 2 0 012-2h12v16H6a2 2 0 00-2 2V5z"/><path d="M18 19H6"/>',
        'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'cash'       => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'user-check' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0112 0"/><path d="M16 12l2 2 4-4"/>',
        'swap'       => '<path d="M7 7h11l-3-3M17 17H6l3 3"/>',
        'undo'       => '<path d="M9 7L4 12l5 5"/><path d="M4 12h11a5 5 0 010 10h-1"/>',
        'tag'        => '<path d="M3 11l8-8 9 9-8 8-9-9z"/><circle cx="8" cy="8" r="1.5"/>',
        'pen'        => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/>',
        'building'   => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/>',
        'grid'       => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/>',
        'chart'      => '<path d="M4 20V4"/><path d="M4 20h16"/><rect x="7" y="11" width="3" height="6"/><rect x="12" y="7" width="3" height="10"/><rect x="17" y="13" width="3" height="4"/>',
        'users'      => '<circle cx="8" cy="8" r="3.5"/><path d="M2 20a6 6 0 0112 0"/><path d="M16 5a3.5 3.5 0 010 7M22 20a6 6 0 00-6-6"/>',
        'cog'        => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'logout'     => '<path d="M15 12H3"/><path d="M11 8l-4 4 4 4"/><path d="M13 3h6a1 1 0 011 1v16a1 1 0 01-1 1h-6"/>',
        'menu'       => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell'       => '<path d="M6 9a6 6 0 0112 0c0 7 3 7 3 7H3s3 0 3-7z"/><path d="M10 20a2 2 0 004 0"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'x-circle'   => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
        'plus'       => '<path d="M12 5v14M5 12h14"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>',
        'edit'       => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/>',
        'trash'      => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
        'x'          => '<path d="M6 6l12 12M18 6L6 18"/>',
        'image'      => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="9" r="1.5"/><path d="M21 16l-5-5L5 20"/>',
        'download'   => '<path d="M12 3v12M7 11l5 5 5-5"/><path d="M4 19h16"/>',
        'doc'        => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 16h6"/>',
        'graduation' => '<path d="M12 4L2 9l10 5 10-5-10-5z"/><path d="M6 11v4c0 1 3 2 6 2s6-1 6-2v-4"/>',
        'shield'     => '<path d="M12 3l8 3v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
        'lock'       => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
        'phone'      => '<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.4 2.1L8 11.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z"/>',
        'dot'        => '<circle cx="12" cy="12" r="4"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['dot'] !!}
</svg>
