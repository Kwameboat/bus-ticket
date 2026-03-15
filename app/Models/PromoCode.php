<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model {
    use SoftDeletes;
    protected $fillable = ['operator_id','code','type','value','min_fare','max_discount','max_uses','used_count','max_per_user','valid_from','valid_to','status'];
    protected function casts(): array { return ['valid_from'=>'datetime','valid_to'=>'datetime','value'=>'decimal:2','min_fare'=>'decimal:2','max_discount'=>'decimal:2']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function bookings() { return $this->hasMany(Booking::class); }

    public function isValid(?float $fare = null): bool {
        if ($this->status !== 'active') return false;
        if ($this->valid_from && now()->lt($this->valid_from)) return false;
        if ($this->valid_to && now()->gt($this->valid_to)) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        if ($fare !== null && $fare < $this->min_fare) return false;
        return true;
    }
    public function calculateDiscount(float $fare): float {
        $discount = $this->type === 'percentage' ? $fare * ($this->value / 100) : $this->value;
        if ($this->max_discount) $discount = min($discount, $this->max_discount);
        return round(min($discount, $fare), 2);
    }
}
