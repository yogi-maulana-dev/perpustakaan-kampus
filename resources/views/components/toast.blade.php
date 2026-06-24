{{-- Toast region: dengarkan event window 'toast' dari Livewire ($this->dispatch('toast', type, message)) --}}
<div x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: detail.type || 'success', message: detail.message || '' });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
        title(type) { return type === 'error' ? 'Gagal' : (type === 'warning' ? 'Perhatian' : 'Berhasil'); }
     }"
     @toast.window="add($event.detail)"
     class="pointer-events-none fixed bottom-5 right-5 z-[60] flex w-[22rem] max-w-[90vw] flex-col gap-3">

    <template x-for="t in toasts" :key="t.id">
        <div class="pointer-events-auto relative flex items-start gap-3 overflow-hidden rounded-xl border border-black/5 bg-white p-4 shadow-xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-10 scale-95"
             x-transition:enter-end="opacity-100 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-10">

            {{-- Ikon --}}
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full"
                 :class="{
                    'bg-emerald-100 text-emerald-600': t.type === 'success',
                    'bg-rose-100 text-rose-600': t.type === 'error',
                    'bg-amber-100 text-amber-600': t.type === 'warning'
                 }">
                <svg x-show="t.type === 'success'" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l2.5 2.5L16 9"/></svg>
                <svg x-show="t.type === 'error'" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                <svg x-show="t.type === 'warning'" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17.5v.5"/></svg>
            </div>

            {{-- Teks --}}
            <div class="flex-1 pt-0.5">
                <p class="text-sm font-bold text-gray-800" x-text="title(t.type)"></p>
                <p class="mt-0.5 text-sm leading-snug text-gray-500" x-text="t.message"></p>
            </div>

            {{-- Tutup --}}
            <button @click="remove(t.id)" class="shrink-0 rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>

            {{-- Progress bar hitung mundur --}}
            <div class="toast-bar absolute bottom-0 left-0 h-1"
                 :class="{
                    'bg-emerald-500': t.type === 'success',
                    'bg-rose-500': t.type === 'error',
                    'bg-amber-500': t.type === 'warning'
                 }"></div>
        </div>
    </template>
</div>

<style>
    .toast-bar { width: 100%; animation: toastbar 4s linear forwards; }
    @keyframes toastbar { from { width: 100%; } to { width: 0%; } }
</style>

@if (session('status'))
    <div x-data x-init="$dispatch('toast', { type: 'success', message: @js(session('status')) })"></div>
@endif
