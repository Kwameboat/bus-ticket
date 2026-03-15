<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fare extends Model
{
    protected $fillable = ['schedule_id', 'boarding_point_id', 'dropoff_point_id', 'base_fare', 'tax_amount', 'service_charge', 'is_active'];

    protected function casts(): array
    {
        return ['base_fare' => 'decimal:2', 'tax_amount' => 'decimal:2', 'service_charge' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->base_fare + (float) $this->tax_amount + (float) $this->service_charge;
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
