<?php

namespace App\Actions\Students;

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectStudent
{
    /**
     * Tolak pendaftaran mahasiswa beserta alasannya.
     */
    public function handle(User $student, ?string $reason = null, ?User $actor = null): User
    {
        return DB::transaction(function () use ($student, $reason, $actor): User {
            $student->update(['status' => UserStatus::Rejected]);

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'student.rejected',
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'description' => "Menolak pendaftaran mahasiswa: {$student->name}".($reason ? " ($reason)" : ''),
                'ip_address' => request()->ip(),
            ]);

            $student->notify(new \App\Notifications\AccountRejected($reason));

            return $student;
        });
    }
}
