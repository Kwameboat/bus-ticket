<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = ['plate', 'capacity', 'type', 'operator_id', 'is_active'];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
