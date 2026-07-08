@props(['a' => 0, 'b' => 0])

<div>
    <x-input-label for="captcha" value="Verifikasi Keamanan" />
    <div class="mt-1 flex items-stretch gap-2">
        <span class="grid shrink-0 select-none place-items-center rounded-lg bg-emerald-100 px-4 text-base font-bold tracking-wider text-emerald-800 whitespace-nowrap">
            {{ $a }} + {{ $b }} = ?
        </span>
        <x-text-input wire:model="captcha" id="captcha" class="block min-w-0 flex-1" type="text" inputmode="numeric" placeholder="Jawaban" required />
        <button type="button" wire:click="newCaptcha" title="Ganti soal"
                class="grid w-11 shrink-0 place-items-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
        </button>
    </div>
    <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
</div>
