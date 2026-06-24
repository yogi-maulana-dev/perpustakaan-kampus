@props([])

<div x-data="{ show: false }" class="relative">
    <input x-bind:type="show ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 pr-10']) }} />
    <button type="button" tabindex="-1" @click="show = !show"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
        <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg x-show="show" style="display:none" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 004.2 4.2"/><path d="M9.9 5.1A9.6 9.6 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3 4.1M6.2 6.2A16.8 16.8 0 002 12s3.5 7 10 7a9.5 9.5 0 003.9-.8"/></svg>
    </button>
</div>
