{{-- Tombol + modal tutorial ganti ukuran foto 3×4 (dikelola Super Admin di menu Tutorial Ganti Ukuran Foto).
     Pakai: @include('partials.modal-tutorial-foto') — opsional ['autoOpen' => false] agar tidak terbuka otomatis. --}}
@php
    $tutAktif = (bool) \App\Models\Setting::get('tutorial_foto_aktif', 1);
    $tutUrl = (string) \App\Models\Setting::get('tutorial_foto_url', '');
    $tutWebsite = (string) \App\Models\Setting::get('tutorial_foto_website', 'https://imgwus.com/id/pas-foto-cpns');
    $tutText = (string) \App\Models\Setting::get(
        'tutorial_foto_text',
        'Untuk mempermudah Anda menjadikan foto ukuran 3×4, Anda bisa mengunjungi website ini dan melihat tutorialnya dengan klik tombol di bawah ini.'
    );
    $tutEmbed = '';
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~i', $tutUrl, $tutM)) {
        $tutEmbed = 'https://www.youtube.com/embed/'.$tutM[1];
    }
@endphp
@if ($tutAktif && ($tutUrl !== '' || $tutWebsite !== ''))
    <div x-data="{ showTutorial: {{ ($autoOpen ?? true) ? 'true' : 'false' }}, showVideo: false }">
        <button type="button" @click="showTutorial = true"
                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5z"/></svg>
            Lihat Tutorial Ganti Ukuran Foto
        </button>

        {{-- Modal (muncul otomatis saat halaman dibuka bila autoOpen) --}}
        <div x-show="showTutorial" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
             @keydown.escape.window="showTutorial = false; showVideo = false">
            <div class="fixed inset-0 bg-black/60" @click="showTutorial = false; showVideo = false"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                 x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <button type="button" @click="showTutorial = false; showVideo = false"
                        class="absolute right-3 top-3 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <x-icon name="x" class="h-5 w-5" />
                </button>

                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5z"/></svg>
                </div>

                <h3 class="mt-3 text-center text-lg font-bold text-gray-800">Tutorial Ganti Ukuran Foto 3×4</h3>
                <p class="mt-2 text-center text-sm text-gray-600">{{ $tutText }}</p>

                {{-- Video muncul di sini setelah tombol YouTube diklik --}}
                @if ($tutEmbed !== '')
                    <div x-show="showVideo" x-cloak x-transition
                         class="mt-4 aspect-video overflow-hidden rounded-lg bg-gray-100">
                        <template x-if="showVideo">
                            <iframe src="{{ $tutEmbed }}?autoplay=1" class="h-full w-full" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        </template>
                    </div>
                @endif

                <div class="mt-4 flex flex-col gap-2">
                    @if ($tutUrl !== '')
                        @if ($tutEmbed !== '')
                            <button type="button" @click="showVideo = !showVideo"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5z"/></svg>
                                <span x-text="showVideo ? 'Sembunyikan Video Tutorial' : 'Tonton Video Tutorial YouTube'"></span>
                            </button>
                        @else
                            <a href="{{ $tutUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5z"/></svg>
                                Lihat Tutorial di YouTube
                            </a>
                        @endif
                    @endif
                    @if ($tutWebsite !== '')
                        <a href="{{ $tutWebsite }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>
                            Kunjungi Website Ubah Ukuran Foto
                        </a>
                    @endif
                    <button type="button" @click="showTutorial = false; showVideo = false"
                            class="rounded-lg border px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Tutup, Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
