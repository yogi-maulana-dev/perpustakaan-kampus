<?php

namespace App\Console\Commands;

use App\Exports\ActivityLogsExport;
use App\Models\ActivityLog;
use App\Models\LocationPing;
use App\Models\LoginAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Rekap & bersihkan log:
 *  - Log lebih lama dari --days (default 3) diarsipkan ke Excel lalu dihapus dari DB.
 *  - Pengaman: log lebih lama dari --purge-days (default 4) dihapus paksa (walau gagal/terlewat diarsipkan)
 *    agar tabel tidak membengkak & query realtime tetap ringan.
 */
class ArchiveActivityLogs extends Command
{
    protected $signature = 'logs:archive {--days=3} {--purge-days=4}';

    protected $description = 'Arsipkan log aktivitas >N hari ke Excel lalu hapus; paksa hapus log >M hari.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $purgeDays = max($days + 1, (int) $this->option('purge-days'));

        $cutoff = now()->subDays($days)->startOfDay();
        $count = ActivityLog::where('created_at', '<', $cutoff)->count();

        if ($count > 0) {
            $file = 'log-archives/rekap-log-'.now()->format('Ymd_His').'.xlsx';
            try {
                Excel::store(new ActivityLogsExport($cutoff), $file, 'local');
                ActivityLog::where('created_at', '<', $cutoff)->delete();
                LoginAttempt::where('created_at', '<', $cutoff)->delete();
                $this->info("Diarsipkan {$count} log ke {$file}, lalu dihapus dari DB.");
            } catch (\Throwable $e) {
                $this->error('Gagal membuat arsip Excel: '.$e->getMessage());
            }
        } else {
            $this->info('Tidak ada log yang perlu diarsipkan.');
        }

        // Pengaman: paksa hapus data lama walau belum sempat diarsipkan.
        $purgeCutoff = now()->subDays($purgeDays)->startOfDay();
        $purged = ActivityLog::where('created_at', '<', $purgeCutoff)->delete();
        LoginAttempt::where('created_at', '<', $purgeCutoff)->delete();
        LocationPing::where('created_at', '<', $purgeCutoff)->delete();

        if ($purged) {
            $this->warn("Hapus paksa {$purged} log lama (>{$purgeDays} hari).");
        }

        // Bersihkan file arsip Excel yang lebih tua dari 3 bulan agar disk tidak penuh.
        $expired = now()->subMonths(3)->timestamp;
        $removed = 0;
        foreach (Storage::disk('local')->files('log-archives') as $f) {
            if (Storage::disk('local')->lastModified($f) < $expired) {
                Storage::disk('local')->delete($f);
                $removed++;
            }
        }
        if ($removed) {
            $this->info("Hapus {$removed} file arsip Excel lama (>3 bulan).");
        }

        return self::SUCCESS;
    }
}
