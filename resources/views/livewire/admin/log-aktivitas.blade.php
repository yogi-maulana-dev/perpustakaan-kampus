<?php

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\BlockedIp;
use App\Models\IpClearance;
use App\Models\LocationPing;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use PragmaRX\Google2FA\Google2FA;

new #[Layout('layouts.dashboard')] #[Title('Log Aktivitas')] class extends Component {
    use WithPagination;

    public string $newIp = '';
    public string $newIpReason = '';
    public string $userSearch = '';
    public string $logFilter = '';
    public bool $autoRefresh = true;

    // Pembebasan IP (butuh konfirmasi 2FA / OTP email Super Admin)
    public bool $showClear = false;
    public string $clearingIp = '';
    public string $clearMethod = '';   // totp | email
    public string $clearCode = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);
        ActivityLogger::log('akses_halaman', 'Membuka halaman Log Aktivitas & Keamanan');
    }

    public function updatingLogFilter(): void { $this->resetPage(); }

    // --- Rekap / arsip / hapus log lama ---------------------------------
    public function archiveNow(): void
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);

        Artisan::call('logs:archive');
        $out = trim(Artisan::output());

        $this->dispatch('toast', type: 'success', message: $out ?: 'Rekap & arsip selesai.');
    }

    public function purgeOld(int $days = 4): void
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);

        $cutoff = now()->subDays(max(1, $days))->startOfDay();
        $n = ActivityLog::where('created_at', '<', $cutoff)->delete();
        LoginAttempt::where('created_at', '<', $cutoff)->delete();
        LocationPing::where('created_at', '<', $cutoff)->delete();

        ActivityLogger::log('log_dihapus', "Menghapus {$n} log lama (>{$days} hari)");
        $this->dispatch('toast', type: $n ? 'warning' : 'success', message: "{$n} log lama dihapus.");
    }

    public function downloadArchive(string $name)
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);

        return redirect()->route('log.archive.download', ['name' => basename($name)]);
    }

    // --- Bebaskan IP dari tanda (khusus Super Admin, konfirmasi kode) ----
    public function startClear(string $ip): void
    {
        $admin = auth()->user();
        $this->clearingIp = $ip;
        $this->clearCode = '';
        $this->resetValidation();

        if ($admin->twoFactorEnabled()) {
            $this->clearMethod = 'totp';
        } else {
            $otp = (string) random_int(100000, 999999);
            Cache::put($this->clearCacheKey(), $otp, now()->addMinutes(10));
            try {
                Mail::raw(
                    "Kode konfirmasi untuk membebaskan IP {$ip}: {$otp}\nBerlaku 10 menit.",
                    fn ($m) => $m->to($admin->email)->subject('Kode Konfirmasi Keamanan — Perpustakaan UML'),
                );
            } catch (\Throwable) {
                $this->dispatch('toast', type: 'error', message: 'Gagal mengirim kode ke email Anda. Periksa pengaturan SMTP.');

                return;
            }
            $this->clearMethod = 'email';
        }

        $this->showClear = true;
    }

    public function confirmClear(): void
    {
        $this->validate(['clearCode' => ['required', 'digits:6']], [], ['clearCode' => 'kode']);

        $admin = auth()->user();
        $valid = $this->clearMethod === 'totp'
            ? (new Google2FA())->verifyKey($admin->two_factor_secret, $this->clearCode, 2)
            : hash_equals((string) Cache::get($this->clearCacheKey()), $this->clearCode);

        if (! $valid) {
            $this->addError('clearCode', 'Kode salah atau kedaluwarsa.');

            return;
        }

        IpClearance::updateOrCreate(
            ['ip_address' => $this->clearingIp],
            ['user_id' => $admin->id, 'email' => $admin->email, 'method' => $this->clearMethod, 'expires_at' => now()->addDay()],
        );
        Cache::forget($this->clearCacheKey());
        ActivityLogger::log('ip_dibebaskan', "IP {$this->clearingIp} dibebaskan dari tanda oleh Super Admin (via {$this->clearMethod})");

        $ip = $this->clearingIp;
        $this->reset('showClear', 'clearingIp', 'clearMethod', 'clearCode');
        $this->dispatch('toast', type: 'success', message: "IP {$ip} dibebaskan dari tanda.");
    }

    public function cancelClear(): void
    {
        $this->reset('showClear', 'clearingIp', 'clearMethod', 'clearCode');
        $this->resetValidation();
    }

    private function clearCacheKey(): string
    {
        return 'ip_clear_otp:'.auth()->id().':'.$this->clearingIp;
    }

    // --- Blokir / buka blokir IP ---------------------------------------
    public function blockIp(string $ip, ?string $reason = null): void
    {
        BlockedIp::firstOrCreate(
            ['ip_address' => $ip],
            ['reason' => $reason, 'blocked_by' => auth()->id()],
        );
        $this->dispatch('toast', type: 'success', message: "IP {$ip} diblokir.");
    }

    public function blockManualIp(): void
    {
        $data = $this->validate([
            'newIp' => ['required', 'ip', 'unique:blocked_ips,ip_address'],
            'newIpReason' => ['nullable', 'string', 'max:255'],
        ], [], ['newIp' => 'alamat IP']);

        $this->blockIp($data['newIp'], $data['newIpReason'] ?: 'Diblokir manual oleh Super Admin');
        $this->reset('newIp', 'newIpReason');
    }

    public function unblockIp(int $id): void
    {
        $ip = BlockedIp::find($id);
        if ($ip) {
            $addr = $ip->ip_address;
            $ip->delete();
            $this->dispatch('toast', type: 'success', message: "Blokir IP {$addr} dibuka.");
        }
    }

    // --- Blokir / aktifkan akun ----------------------------------------
    public function suspendUser(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin')) {
            $this->dispatch('toast', type: 'error', message: 'Akun Super Admin tidak dapat diblokir.');
            return;
        }

        $user->update(['status' => UserStatus::Suspended]);
        $this->dispatch('toast', type: 'warning', message: "Akun {$user->name} diblokir.");
    }

    public function activateUser(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['status' => UserStatus::Active]);
        $this->dispatch('toast', type: 'success', message: "Akun {$user->name} diaktifkan.");
    }

    public function with(): array
    {
        $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $suspicious = LoginAttempt::query()
            ->whereDate('created_at', today())
            ->selectRaw('ip_address, count(*) as attempts, sum(case when successful then 0 else 1 end) as failed, max(created_at) as last_at')
            ->whereNotNull('ip_address')
            // IP yang sudah dibebaskan (verifikasi 2FA/OTP) tidak lagi ditandai.
            ->whereNotIn('ip_address', fn ($q) => $q->select('ip_address')->from('ip_clearances')->where('expires_at', '>', now()))
            ->groupBy('ip_address')
            ->havingRaw('count(*) > 5')
            ->orderByDesc('attempts')
            ->get()
            ->map(function ($row) {
                $row->emails = LoginAttempt::whereDate('created_at', today())
                    ->where('ip_address', $row->ip_address)
                    ->whereNotNull('email')->distinct()->pluck('email')->take(5)->all();
                $row->is_blocked = BlockedIp::isBlocked($row->ip_address);
                $row->location = LocationPing::where('ip_address', $row->ip_address)->latest()->first();

                return $row;
            });

        return [
            'stats' => [
                'users' => User::count(),
                'activities_today' => ActivityLog::whereDate('created_at', today())->count(),
                'failed_today' => LoginAttempt::whereDate('created_at', today())->where('successful', false)->count(),
                'suspicious' => $suspicious->count(),
                'blocked_ips' => BlockedIp::count(),
                'blocked_accounts' => User::where('status', UserStatus::Suspended)->count(),
            ],
            'suspicious' => $suspicious,
            'locations' => LocationPing::latest()->limit(30)->get(),
            'blockedIps' => BlockedIp::with('blocker')->latest()->get(),
            'blockedAccounts' => User::with('roles')->where('status', UserStatus::Suspended)->latest()->get(),
            'foundUsers' => strlen($this->userSearch) >= 2
                ? User::with('roles')->where('status', UserStatus::Active)
                    ->where(fn ($q) => $q->where('name', $op, "%{$this->userSearch}%")->orWhere('email', $op, "%{$this->userSearch}%"))
                    ->limit(8)->get()
                : collect(),
            'logs' => ActivityLog::with('user')
                ->when($this->logFilter, fn ($q) => $q->where(fn ($s) => $s
                    ->where('action', $op, "%{$this->logFilter}%")
                    ->orWhere('user_name', $op, "%{$this->logFilter}%")
                    ->orWhere('description', $op, "%{$this->logFilter}%")))
                ->latest()->paginate(15),
            'archives' => collect(Storage::disk('local')->files('log-archives'))
                ->sortDesc()->take(12)
                ->map(fn ($p) => [
                    'name' => basename($p),
                    'size' => round(Storage::disk('local')->size($p) / 1024, 1),
                ])->values(),
        ];
    }
}; ?>

<div class="space-y-6" @if ($autoRefresh) wire:poll.20s @endif>
    {{-- Kontrol: auto-refresh + rekap/arsip --}}
    <div class="flex flex-col gap-3 rounded-xl border bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model.live="autoRefresh" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            <span>Auto-refresh
                <span class="text-gray-400">(tiap 20 dtk, berhenti otomatis saat tab tidak aktif)</span>
                @if ($autoRefresh)
                    <span class="ml-1 inline-flex items-center gap-1 text-emerald-600"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>live</span>
                @endif
            </span>
        </label>
        <div class="flex flex-wrap gap-2">
            <button wire:click="archiveNow" wire:confirm="Arsipkan log lebih lama dari 3 hari ke Excel lalu hapus dari database?"
                    wire:loading.attr="disabled" wire:target="archiveNow"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                <x-icon name="download" class="h-4 w-4" /> Rekap &amp; Arsip (Excel)
            </button>
            <button wire:click="purgeOld(4)" wire:confirm="Hapus permanen log yang lebih lama dari 4 hari?"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">
                <x-icon name="trash" class="h-4 w-4" /> Hapus Log &gt;4 Hari
            </button>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        @foreach ([
            ['Total User', $stats['users'], 'users', 'emerald'],
            ['Aktivitas Hari Ini', $stats['activities_today'], 'chart', 'emerald'],
            ['Login Gagal (hari ini)', $stats['failed_today'], 'lock', 'amber'],
            ['IP Mencurigakan', $stats['suspicious'], 'shield', 'rose'],
            ['IP Diblokir', $stats['blocked_ips'], 'x-circle', 'zinc'],
            ['Akun Diblokir', $stats['blocked_accounts'], 'user-check', 'zinc'],
        ] as [$label, $val, $icon, $color])
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2 text-{{ $color }}-600">
                    <x-icon name="{{ $icon }}" class="h-5 w-5" />
                    <span class="text-2xl font-bold text-gray-800">{{ $val }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- IP Mencurigakan --}}
    <div class="rounded-xl border bg-white shadow-sm">
        <div class="border-b px-5 py-3">
            <h3 class="font-semibold text-gray-800">IP Mencurigakan Hari Ini <span class="text-xs font-normal text-gray-400">(&gt; 5 percobaan login)</span></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-2.5">IP Address</th>
                        <th class="px-5 py-2.5">Total</th>
                        <th class="px-5 py-2.5">Gagal</th>
                        <th class="px-5 py-2.5">Akun Dicoba</th>
                        <th class="px-5 py-2.5">Lokasi</th>
                        <th class="px-5 py-2.5">Terakhir</th>
                        <th class="px-5 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($suspicious as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-2.5 font-mono text-gray-800">{{ $s->ip_address }}</td>
                            <td class="px-5 py-2.5"><x-badge color="amber">{{ $s->attempts }}</x-badge></td>
                            <td class="px-5 py-2.5 text-rose-600">{{ $s->failed }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-500">{{ implode(', ', $s->emails) ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-xs">
                                @if ($s->location)
                                    <a href="{{ $s->location->mapsUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-emerald-700 hover:underline">
                                        <x-icon name="dot" class="h-3 w-3" /> Lihat Peta
                                    </a>
                                    <span class="block text-[11px] text-gray-400">±{{ $s->location->accuracy ?? '?' }} m</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($s->last_at)->format('H:i') }}</td>
                            <td class="px-5 py-2.5 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="startClear('{{ $s->ip_address }}')"
                                            class="rounded-md border border-emerald-300 px-2.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Bebaskan</button>
                                    @if ($s->is_blocked)
                                        <span class="text-xs text-gray-400">Sudah diblokir</span>
                                    @else
                                        <button wire:click="blockIp('{{ $s->ip_address }}', 'Terdeteksi mencurigakan')" wire:confirm="Blokir IP {{ $s->ip_address }}?"
                                                class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">Blokir IP</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">Tidak ada IP mencurigakan hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Lokasi Dibagikan --}}
    <div class="rounded-xl border bg-white shadow-sm">
        <div class="border-b px-5 py-3">
            <h3 class="font-semibold text-gray-800">Lokasi Dibagikan <span class="text-xs font-normal text-gray-400">(pengunjung yang menekan "Buka" & mengizinkan lokasi)</span></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-2.5">Waktu</th>
                        <th class="px-5 py-2.5">IP</th>
                        <th class="px-5 py-2.5">Email</th>
                        <th class="px-5 py-2.5">Koordinat</th>
                        <th class="px-5 py-2.5">Akurasi</th>
                        <th class="px-5 py-2.5 text-right">Peta</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($locations as $loc)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-2.5 text-xs text-gray-500">{{ $loc->created_at?->format('d/m H:i:s') }}</td>
                            <td class="px-5 py-2.5 font-mono text-xs text-gray-700">{{ $loc->ip_address ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-600">{{ $loc->email ?: '— (tamu)' }}</td>
                            <td class="px-5 py-2.5 font-mono text-xs text-gray-500">{{ number_format($loc->latitude, 5) }}, {{ number_format($loc->longitude, 5) }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-500">±{{ $loc->accuracy ?? '?' }} m</td>
                            <td class="px-5 py-2.5 text-right">
                                <a href="{{ $loc->mapsUrl() }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 rounded-md border border-emerald-200 px-2.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                    Lihat Peta &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">Belum ada lokasi yang dibagikan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- IP Diblokir --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="font-semibold text-gray-800">IP Diblokir</h3>
            </div>
            <div class="p-5">
                <form wire:submit="blockManualIp" class="mb-4 flex flex-wrap items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600">Blokir IP manual</label>
                        <input wire:model="newIp" type="text" placeholder="mis. 192.168.1.10" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('newIp') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600">Alasan (opsional)</label>
                        <input wire:model="newIpReason" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    </div>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Blokir</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y text-sm">
                        <tbody class="divide-y">
                            @forelse ($blockedIps as $b)
                                <tr>
                                    <td class="py-2 pr-2 font-mono text-gray-800">{{ $b->ip_address }}</td>
                                    <td class="py-2 pr-2 text-xs text-gray-500">{{ $b->reason }}</td>
                                    <td class="py-2 text-right">
                                        <button wire:click="unblockIp({{ $b->id }})" wire:confirm="Buka blokir IP ini?"
                                                class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Buka Blokir</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="py-6 text-center text-gray-400">Belum ada IP diblokir.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Blokir Akun --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="font-semibold text-gray-800">Blokir Akun</h3>
            </div>
            <div class="p-5">
                <label class="block text-xs font-medium text-gray-600">Cari akun aktif untuk diblokir</label>
                <input wire:model.live.debounce.400ms="userSearch" type="text" placeholder="Nama / email…" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />

                @if (count($foundUsers))
                    <div class="mt-2 divide-y rounded-lg border">
                        @foreach ($foundUsers as $u)
                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $u->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $u->email }} · {{ $u->roles->first()?->name ?? 'Anggota' }}</p>
                                </div>
                                @unless ($u->hasRole('Super Admin'))
                                    <button wire:click="suspendUser({{ $u->id }})" wire:confirm="Blokir akun {{ $u->name }}?"
                                            class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">Blokir</button>
                                @else
                                    <span class="text-xs text-gray-400">Super Admin</span>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endif

                <h4 class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Akun Diblokir</h4>
                <div class="divide-y">
                    @forelse ($blockedAccounts as $u)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <p class="font-medium text-gray-800">{{ $u->name }}</p>
                                <p class="text-xs text-gray-500">{{ $u->email }} · {{ $u->roles->first()?->name ?? 'Anggota' }}</p>
                            </div>
                            <button wire:click="activateUser({{ $u->id }})" wire:confirm="Aktifkan kembali akun {{ $u->name }}?"
                                    class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Aktifkan</button>
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-400">Tidak ada akun diblokir.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Log Aktivitas --}}
    <div class="rounded-xl border bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-semibold text-gray-800">Log Aktivitas User</h3>
            <input wire:model.live.debounce.400ms="logFilter" type="text" placeholder="Filter aksi / nama / detail…"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 sm:w-72" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-2.5">Waktu</th>
                        <th class="px-5 py-2.5">User</th>
                        <th class="px-5 py-2.5">Role</th>
                        <th class="px-5 py-2.5">Email</th>
                        <th class="px-5 py-2.5">Aktivitas</th>
                        <th class="px-5 py-2.5">Detail</th>
                        <th class="px-5 py-2.5">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-2.5 text-xs text-gray-500">{{ $log->created_at->format('d/m H:i:s') }}</td>
                            <td class="px-5 py-2.5 text-gray-800">{{ $log->user_name ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-500">{{ $log->user_role ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-600">
                                @if ($log->email)
                                    {{ $log->email }}
                                @else
                                    <span class="text-gray-300" title="Tanpa email — identifikasi via IP">— (IP)</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5"><x-badge :color="$log->badgeColor()">{{ $log->action }}</x-badge></td>
                            <td class="px-5 py-2.5 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($log->description, 60) ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-2.5 font-mono text-xs text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $logs->links() }}</div>
    </div>

    {{-- Arsip rekap (Excel) --}}
    <div class="rounded-xl border bg-white shadow-sm">
        <div class="border-b px-5 py-3">
            <h3 class="font-semibold text-gray-800">Arsip Rekap Log (Excel) <span class="text-xs font-normal text-gray-400">— otomatis tiap hari 01:00 untuk log &gt;3 hari</span></h3>
        </div>
        <div class="p-5">
            @if (count($archives))
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($archives as $a)
                        <button wire:click="downloadArchive('{{ $a['name'] }}')"
                                class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-left text-sm hover:bg-gray-50">
                            <span class="flex items-center gap-2 truncate">
                                <x-icon name="doc" class="h-4 w-4 shrink-0 text-emerald-600" />
                                <span class="truncate text-gray-700">{{ $a['name'] }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-gray-400">{{ $a['size'] }} KB</span>
                        </button>
                    @endforeach
                </div>
            @else
                <p class="text-center text-sm text-gray-400">Belum ada arsip. Klik "Rekap &amp; Arsip (Excel)" di atas atau tunggu jadwal harian.</p>
            @endif
        </div>
    </div>

    {{-- Modal konfirmasi bebaskan IP --}}
    @if ($showClear)
        <x-modal-overlay wire:click.self="cancelClear">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-800">Bebaskan IP {{ $clearingIp }}</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($clearMethod === 'totp')
                        Konfirmasi dengan <strong>6 digit kode Google Authenticator</strong> Anda.
                    @else
                        Kode <strong>6 digit</strong> telah dikirim ke email Super Admin. Masukkan di bawah.
                    @endif
                </p>
                <div class="mt-4">
                    <input wire:model="clearCode" wire:keydown.enter="confirmClear" type="text" inputmode="numeric" maxlength="6"
                           class="w-full rounded-lg border-gray-300 text-center text-lg tracking-[0.4em] focus:border-emerald-500 focus:ring-emerald-500" placeholder="000000" />
                    @error('clearCode') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button wire:click="cancelClear" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="confirmClear" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Bebaskan</button>
                </div>
            </div>
        </x-modal-overlay>
    @endif
</div>
