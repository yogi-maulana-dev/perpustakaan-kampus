<?php

namespace App\Imports;

use App\Enums\MemberType;
use App\Enums\UserStatus;
use App\Models\MahasiswaProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import anggota (mahasiswa/dosen/umum) dari Excel/CSV.
 * Kolom minimal: Nama. Anggota hasil import langsung berstatus AKTIF
 * (dianggap sudah diverifikasi admin) dan diberi peran "Anggota".
 * Password default = kolom Password → jika kosong pakai nomor identitas → jika kosong "anggota123".
 */
class MembersImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $row = $row->toArray();

            $nama = $this->pick($row, ['nama', 'nama lengkap', 'name']);
            if (! $nama) {
                $this->skipped++;
                continue;
            }

            $tipeRaw = strtolower((string) ($this->pick($row, ['tipe', 'jenis', 'type']) ?? 'mahasiswa'));
            $tipe = in_array($tipeRaw, MemberType::values(), true) ? $tipeRaw : 'mahasiswa';

            $nim = $this->pick($row, ['nim']);
            $nidn = $this->pick($row, ['nidn']);
            $nbm = $this->pick($row, ['nbm']);
            $identitas = $this->pick($row, ['nomor identitas', 'no ktp', 'ktp', 'nik']);
            $nomorUtama = $nim ?? $nidn ?? $nbm ?? $identitas;

            $email = $this->pick($row, ['email', 'e-mail']);
            if (! $email) {
                $email = $this->generateEmail($nomorUtama ?? $nama);
            }
            $email = strtolower($email);

            // Lewati bila email sudah dipakai (hindari duplikat).
            if (User::where('email', $email)->exists()) {
                $this->skipped++;
                continue;
            }

            $passwordPlain = $this->pick($row, ['password', 'kata sandi']) ?: ($nomorUtama ?: 'anggota123');

            $user = User::create([
                'name' => $nama,
                'email' => $email,
                'password' => Hash::make($passwordPlain),
                'status' => UserStatus::Active,
            ]);
            $user->assignRole('Anggota');

            MahasiswaProfile::create([
                'user_id' => $user->id,
                'tipe' => $tipe,
                'nim' => $nim,
                'nidn' => $nidn,
                'nbm' => $nbm,
                'nomor_identitas' => $identitas,
                'fakultas' => $this->pick($row, ['fakultas']),
                'program_studi' => $this->pick($row, ['program studi', 'prodi', 'jurusan']),
                'kode_prodi' => $this->pick($row, ['kode prodi', 'kode']),
                'jenjang' => $this->pick($row, ['jenjang', 'strata']),
                'angkatan' => $this->pick($row, ['angkatan', 'tahun masuk']),
                'pekerjaan' => $this->pick($row, ['pekerjaan']),
                'instansi' => $this->pick($row, ['instansi', 'asal instansi']),
                'no_hp' => $this->pick($row, ['no hp', 'no. hp', 'nomor hp', 'hp', 'telepon', 'whatsapp']) ?? '',
            ]);

            $this->imported++;
        }
    }

    private function pick(array $row, array $aliases): ?string
    {
        $norm = [];
        foreach ($row as $k => $v) {
            $norm[preg_replace('/[^a-z0-9]/', '', strtolower((string) $k))] = $v;
        }

        foreach ($aliases as $alias) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower($alias));
            if (isset($norm[$key]) && trim((string) $norm[$key]) !== '') {
                return trim((string) $norm[$key]);
            }
        }

        return null;
    }

    private function generateEmail(string $seed): string
    {
        $base = Str::slug($seed, '.') ?: 'anggota';
        $email = $base.'@anggota.local';
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base.($i++).'@anggota.local';
        }

        return $email;
    }
}
