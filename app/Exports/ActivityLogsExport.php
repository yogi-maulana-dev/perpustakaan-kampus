<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Rekap log aktivitas yang lebih lama dari batas waktu tertentu.
 * FromQuery → dibaca per-chunk agar hemat memori (tidak membebani server).
 */
class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private CarbonInterface $before) {}

    public function query()
    {
        return ActivityLog::query()->where('created_at', '<', $this->before)->orderBy('id');
    }

    public function headings(): array
    {
        return ['Waktu', 'User', 'Role', 'Email', 'Aktivitas', 'Detail', 'IP', 'User Agent'];
    }

    /** @param ActivityLog $log */
    public function map($log): array
    {
        return [
            $log->created_at?->format('Y-m-d H:i:s'),
            $log->user_name,
            $log->user_role,
            $log->email,
            $log->action,
            $log->description,
            $log->ip_address,
            $log->user_agent,
        ];
    }

    public function title(): string
    {
        return 'Rekap Log Aktivitas';
    }
}
