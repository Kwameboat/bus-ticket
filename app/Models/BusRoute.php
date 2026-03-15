<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusRoute extends Model
{
    protected $table = 'routes';

    protected $fillable = ['origin_city_id', 'destination_city_id', 'distance_km', 'duration_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function originCity()
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'route_id');
    }
}
