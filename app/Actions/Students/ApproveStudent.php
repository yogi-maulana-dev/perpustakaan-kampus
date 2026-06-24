<?php

namespace App\Actions\Students;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveStudent
{
    /**
     * Setujui pendaftaran mahasiswa: aktifkan akun + beri role Mahasiswa.
     */
    public function handle(User $student, ?User $actor = null): User
    {
        return DB::transaction(function () use ($student, $actor): User {
            $student->update([
                'status' => UserStatus::Active,
                'email_verified_at' => $student->email_verified_at ?? now(),
            ]);

            if (! $student->hasRole(RoleName::Anggota->value)) {
                $student->assignRole(RoleName::Anggota->value);
            }

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'student.approved',
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'description' => "Menyetujui pendaftaran mahasiswa: {$student->name}",
                'ip_address' => request()->ip(),
            ]);

            // Notifikasi "Akun diterima" dikirim pada Tahap 7.
            $student->notify(new \App\Notifications\AccountApproved());

            return $student;
        });
    }
}
