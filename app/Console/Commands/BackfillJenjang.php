<?php

namespace App\Console\Commands;

use App\Models\MahasiswaProfile;
use Illuminate\Console\Command;

class BackfillJenjang extends Command
{
    protected $signature = 'akademik:backfill-jenjang {--force : Timpa jenjang yang sudah ada}';

    protected $description = 'Isi kolom jenjang (S1/D3) data anggota lama berdasarkan kode prodi dari config/akademik.';

    public function handle(): int
    {
        // Peta kode prodi => data dari config.
        $byKode = [];
        foreach (config('akademik') as $fakultas) {
            foreach ($fakultas['prodi'] as $prodi) {
                $byKode[$prodi['kode']] = [
                    'jenjang' => $prodi['jenjang'],
                    'program_studi' => $prodi['nama'],
                    'fakultas' => $fakultas['nama'],
                ];
            }
        }

        $force = (bool) $this->option('force');
        $updated = 0;

        MahasiswaProfile::whereNotNull('kode_prodi')->chunkById(200, function ($profiles) use ($byKode, $force, &$updated) {
            foreach ($profiles as $p) {
                $ref = $byKode[$p->kode_prodi] ?? null;
                if (! $ref) {
                    continue;
                }

                $payload = [];
                if ($force || blank($p->jenjang)) {
                    $payload['jenjang'] = $ref['jenjang'];
                }
                if ($force || blank($p->program_studi)) {
                    $payload['program_studi'] = $ref['program_studi'];
                }
                if ($force || blank($p->fakultas)) {
                    $payload['fakultas'] = $ref['fakultas'];
                }

                if ($payload) {
                    $p->update($payload);
                    $updated++;
                }
            }
        });

        $this->info("Selesai. {$updated} profil anggota diperbarui.");

        return self::SUCCESS;
    }
}
