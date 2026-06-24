<?php

use App\Enums\MemberType;
use App\Enums\UserStatus;
use App\Models\MahasiswaProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.guest')] class extends Component {
    use WithFileUploads;

    public string $tipe = 'mahasiswa';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $no_hp = '';
    public $ktm = null;

    // Mahasiswa / Dosen akademik
    public string $fakultas = '';     // kode fakultas (key config akademik)
    public string $kode_prodi = '';   // kode prodi
    public string $nim = '';
    public string $angkatan = '';
    // Dosen — pilih salah satu identitas: 'nidn' atau 'nbm'
    public string $dosen_id = 'nidn';
    public string $nidn = '';
    public string $nbm = '';
    // Umum
    public string $nomor_identitas = '';
    public string $pekerjaan = '';
    public string $instansi = '';

    /** Reset prodi ketika fakultas berganti. */
    public function updatedFakultas(): void
    {
        $this->kode_prodi = '';
    }

    /** Bersihkan field identitas dosen yang tidak dipilih. */
    public function updatedDosenId(): void
    {
        if ($this->dosen_id === 'nidn') {
            $this->nbm = '';
        } else {
            $this->nidn = '';
        }
        $this->resetValidation(['nidn', 'nbm']);
    }

    public function register(): void
    {
        $isMhs = $this->tipe === 'mahasiswa';
        $isDosen = $this->tipe === 'dosen';
        $isUmum = $this->tipe === 'umum';
        $butuhAkademik = $isMhs || $isDosen;

        $faculties = config('akademik');
        $prodiCodes = ($this->fakultas && isset($faculties[$this->fakultas]))
            ? array_column($faculties[$this->fakultas]['prodi'], 'kode')
            : [];

        $validated = $this->validate([
            'tipe' => ['required', Rule::in(MemberType::values())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'ktm' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],

            'fakultas' => [Rule::requiredIf($butuhAkademik), 'nullable', Rule::in(array_keys($faculties))],
            'kode_prodi' => [Rule::requiredIf($butuhAkademik), 'nullable', Rule::in($prodiCodes)],

            'nim' => [Rule::requiredIf($isMhs), 'nullable', 'string', 'max:30', 'unique:mahasiswa_profiles,nim'],
            'angkatan' => [Rule::requiredIf($isMhs), 'nullable', 'digits:4'],

            'dosen_id' => [Rule::requiredIf($isDosen), 'nullable', Rule::in(['nidn', 'nbm'])],
            'nidn' => [Rule::requiredIf($isDosen && $this->dosen_id === 'nidn'), 'nullable', 'string', 'max:30'],
            'nbm' => [Rule::requiredIf($isDosen && $this->dosen_id === 'nbm'), 'nullable', 'string', 'max:30'],

            'nomor_identitas' => [Rule::requiredIf($isUmum), 'nullable', 'string', 'max:30'],
            'pekerjaan' => [Rule::requiredIf($isUmum), 'nullable', 'string', 'max:255'],
            'instansi' => ['nullable', 'string', 'max:255'],
        ], [], [
            'no_hp' => 'nomor HP',
            'ktm' => 'kartu identitas',
            'nim' => 'NIM',
            'nidn' => 'NIDN',
            'nbm' => 'NBM',
            'kode_prodi' => 'program studi',
            'nomor_identitas' => 'nomor KTP',
        ]);

        // Derive nama fakultas & prodi dari kode.
        $namaFakultas = null;
        $namaProdi = null;
        $jenjang = null;
        if ($butuhAkademik && isset($faculties[$this->fakultas])) {
            $namaFakultas = $faculties[$this->fakultas]['nama'];
            $prodi = collect($faculties[$this->fakultas]['prodi'])->firstWhere('kode', $this->kode_prodi);
            $namaProdi = $prodi['nama'] ?? null;
            $jenjang = $prodi['jenjang'] ?? null;
        }

        $ktmPath = $this->ktm->store('ktm', 'public');

        DB::transaction(function () use ($validated, $ktmPath, $namaFakultas, $namaProdi, $jenjang): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => UserStatus::Pending,
            ]);

            // String kosong → null agar tidak melanggar unique (mis. nim '' pada dosen/umum).
            $nullIfEmpty = fn (?string $v) => ($v === null || $v === '') ? null : $v;

            MahasiswaProfile::create([
                'user_id' => $user->id,
                'tipe' => $validated['tipe'],
                'nim' => $nullIfEmpty($validated['nim'] ?? null),
                'nidn' => $nullIfEmpty($validated['nidn'] ?? null),
                'nbm' => $nullIfEmpty($validated['nbm'] ?? null),
                'nomor_identitas' => $nullIfEmpty($validated['nomor_identitas'] ?? null),
                'fakultas' => $namaFakultas,
                'program_studi' => $namaProdi,
                'kode_prodi' => $nullIfEmpty($validated['kode_prodi'] ?? null),
                'jenjang' => $jenjang,
                'angkatan' => $nullIfEmpty($validated['angkatan'] ?? null),
                'pekerjaan' => $nullIfEmpty($validated['pekerjaan'] ?? null),
                'instansi' => $nullIfEmpty($validated['instansi'] ?? null),
                'no_hp' => $validated['no_hp'],
                'ktm_path' => $ktmPath,
            ]);
        });

        session()->flash('status', 'Pendaftaran berhasil! Akun Anda menunggu persetujuan pustakawan. Anda akan dapat login setelah disetujui.');

        $this->redirect(route('login'), navigate: true);
    }

    public function with(): array
    {
        return ['faculties' => config('akademik')];
    }
}; ?>

<div>
    <h2 class="mb-1 text-lg font-semibold text-emerald-900">Pendaftaran Anggota</h2>
    <p class="mb-4 text-sm text-gray-500">Pilih jenis keanggotaan, lalu lengkapi data. Akun aktif setelah disetujui pustakawan.</p>

    {{-- Pilih tipe --}}
    <div class="mb-5 grid grid-cols-3 gap-2">
        @foreach (['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'umum' => 'Umum'] as $val => $label)
            <button type="button" wire:click="$set('tipe', '{{ $val }}')"
                    class="rounded-lg border px-3 py-2 text-sm font-medium transition
                        {{ $tipe === $val ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-gray-300 text-gray-600 hover:border-emerald-400' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <form wire:submit="register" enctype="multipart/form-data">
        <div>
            <x-input-label for="name" value="Nama Lengkap" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Identitas Mahasiswa --}}
        @if ($tipe === 'mahasiswa')
            <div class="mt-4">
                <x-input-label for="nim" value="NIM" />
                <x-text-input wire:model="nim" id="nim" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nim')" class="mt-2" />
            </div>
        @endif

        {{-- Identitas Dosen — pilih salah satu: NIDN atau NBM --}}
        @if ($tipe === 'dosen')
            <div class="mt-4">
                <x-input-label value="Identitas Dosen (pilih salah satu)" />
                <p class="mt-0.5 text-xs text-gray-500">Belum punya NIDN? Gunakan NBM (Nomor Baku Muhammadiyah).</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach (['nidn' => 'Punya NIDN', 'nbm' => 'Pakai NBM'] as $val => $label)
                        <button type="button" wire:click="$set('dosen_id', '{{ $val }}')" wire:loading.attr="disabled"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition
                                    {{ $dosen_id === $val ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-gray-300 text-gray-600 hover:border-emerald-400' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('dosen_id')" class="mt-2" />

                @if ($dosen_id === 'nidn')
                    <div class="mt-3">
                        <x-input-label for="nidn" value="NIDN" />
                        <x-text-input wire:model="nidn" id="nidn" class="block mt-1 w-full" type="text" placeholder="Nomor Induk Dosen Nasional" />
                        <x-input-error :messages="$errors->get('nidn')" class="mt-2" />
                    </div>
                @else
                    <div class="mt-3">
                        <x-input-label for="nbm" value="NBM (Nomor Baku Muhammadiyah)" />
                        <x-text-input wire:model="nbm" id="nbm" class="block mt-1 w-full" type="text" placeholder="Nomor Baku Muhammadiyah" />
                        <x-input-error :messages="$errors->get('nbm')" class="mt-2" />
                    </div>
                @endif
            </div>
        @endif

        {{-- Fakultas & Program Studi (mahasiswa & dosen) --}}
        @if ($tipe === 'mahasiswa' || $tipe === 'dosen')
            <div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="fakultas" value="Fakultas" />
                    <select wire:model.live="fakultas" id="fakultas"
                            class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— pilih fakultas —</option>
                        @foreach ($faculties as $kode => $f)
                            <option value="{{ $kode }}">{{ $f['nama'] }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('fakultas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="kode_prodi" value="Program Studi (Kode)" />
                    <select wire:model="kode_prodi" id="kode_prodi" @disabled(! $fakultas)
                            class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-gray-100">
                        <option value="">{{ $fakultas ? '— pilih program studi —' : 'Pilih fakultas dulu' }}</option>
                        @foreach (($faculties[$fakultas]['prodi'] ?? []) as $pr)
                            <option value="{{ $pr['kode'] }}">{{ $pr['kode'] }} — {{ $pr['nama'] }} ({{ $pr['jenjang'] }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('kode_prodi')" class="mt-2" />
                </div>
            </div>
        @endif

        {{-- Angkatan (mahasiswa) --}}
        @if ($tipe === 'mahasiswa')
            <div class="mt-4">
                <x-input-label for="angkatan" value="Angkatan (tahun)" />
                <x-text-input wire:model="angkatan" id="angkatan" class="block mt-1 w-full" type="text" inputmode="numeric" placeholder="2024" />
                <x-input-error :messages="$errors->get('angkatan')" class="mt-2" />
            </div>
        @endif

        {{-- Field Umum --}}
        @if ($tipe === 'umum')
            <div class="mt-4">
                <x-input-label for="nomor_identitas" value="Nomor KTP" />
                <x-text-input wire:model="nomor_identitas" id="nomor_identitas" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nomor_identitas')" class="mt-2" />
            </div>
            <div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="pekerjaan" value="Pekerjaan" />
                    <x-text-input wire:model="pekerjaan" id="pekerjaan" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('pekerjaan')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="instansi" value="Instansi (opsional)" />
                    <x-text-input wire:model="instansi" id="instansi" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('instansi')" class="mt-2" />
                </div>
            </div>
        @endif

        <div class="mt-4">
            <x-input-label for="no_hp" value="Nomor HP" />
            <x-text-input wire:model="no_hp" id="no_hp" class="block mt-1 w-full" type="text" inputmode="tel" placeholder="08xxxxxxxxxx" />
            <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-password-input wire:model="password" id="password" class="block mt-1 w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="ktm" value="Upload Kartu Identitas (KTM/KTP/Kartu Dosen — JPG/PNG/PDF, maks 2MB)" />
            <input wire:model="ktm" id="ktm" type="file" accept=".jpg,.jpeg,.png,.pdf"
                   class="block mt-1 w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
            <div wire:loading wire:target="ktm" class="mt-1 text-xs text-gray-500">Mengunggah berkas…</div>
            <x-input-error :messages="$errors->get('ktm')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900" href="{{ route('login') }}" wire:navigate>
                Sudah punya akun?
            </a>
            <button type="submit" wire:loading.attr="disabled" wire:target="register, ktm"
                    class="ms-4 inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                Daftar
            </button>
        </div>
    </form>
</div>
