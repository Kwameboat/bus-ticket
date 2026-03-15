<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['route_id', 'bus_id', 'operator_id', 'departure_date', 'departure_time', 'arrival_time', 'status'];

    public function route()
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
