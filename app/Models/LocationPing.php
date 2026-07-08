<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPing extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'ip_address',
        'email',
        'latitude',
        'longitude',
        'accuracy',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** Tautan Google Maps ke koordinat ini. */
    public function mapsUrl(): string
    {
        return 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude;
    }
}
