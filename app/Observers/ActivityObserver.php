<?php

namespace App\Observers;

use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer generik: mencatat tambah/ubah/hapus data model bisnis ke activity_logs.
 * Didaftarkan untuk model tertentu di AppServiceProvider.
 */
class ActivityObserver
{
    public function created(Model $model): void
    {
        ActivityLogger::log($this->key($model, 'created'), 'Data baru dibuat (ID #'.$model->getKey().')', $model);
    }

    public function updated(Model $model): void
    {
        $changed = collect($model->getChanges())
            ->except(['updated_at', 'remember_token', 'password', 'two_factor_secret'])
            ->keys()
            ->implode(', ');

        ActivityLogger::log($this->key($model, 'updated'), $changed ? 'Field diubah: '.$changed : 'Data diperbarui', $model);
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::log($this->key($model, 'deleted'), 'Data dihapus (ID #'.$model->getKey().')', $model);
    }

    private function key(Model $model, string $event): string
    {
        return strtolower(class_basename($model)).'_'.$event;
    }
}
