<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = ['bus_id', 'schedule_id', 'booking_id', 'seat_number', 'status', 'locked_until'];

    protected function casts(): array
    {
        return ['locked_until' => 'datetime'];
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
