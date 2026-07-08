<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat jatuh tempo & penandaan keterlambatan setiap hari pukul 08:00.
Schedule::command('loans:remind')->dailyAt('08:00');

// Rekap log aktivitas ke Excel (>3 hari) + hapus paksa (>4 hari) tiap hari 01:00.
Schedule::command('logs:archive')->dailyAt('01:00');
