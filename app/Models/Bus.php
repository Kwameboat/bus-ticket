<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bus extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['operator_id','name','reg_number','plate_number','bus_type','capacity','amenities','color','make','model','year','image','status'];
    protected function casts(): array { return ['amenities'=>'array']; }
    public function operator()  { return $this->belongsTo(Operator::class); }
    public function seatPlan()  { return $this->hasOne(SeatPlan::class)->where('is_default',true); }
    public function seatPlans() { return $this->hasMany(SeatPlan::class); }
    public function schedules() { return $this->hasMany(Schedule::class); }
    public function trips()     { return $this->hasMany(Trip::class); }
    public function getImageUrlAttribute(): string {
        return $this->image ? asset('storage/'.$this->image) : asset('images/default-bus.png');
    }
}
