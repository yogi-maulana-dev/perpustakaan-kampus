<div {{ $attributes }}
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
     x-data x-on:keydown.escape.window="$wire.set('detailId', null)">
    {{ $slot }}
</div>
