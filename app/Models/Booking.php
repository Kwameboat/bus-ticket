<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['user_id', 'schedule_id', 'reference', 'status', 'total_amount', 'payment_reference', 'paid_at', 'boarded', 'boarded_at'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_at'      => 'datetime',
            'boarded_at'   => 'datetime',
            'boarded'      => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
