<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reschedule extends Model {
    protected $fillable = ['booking_id','user_id','old_trip_id','new_trip_id','fare_difference','status','notes'];
    protected function casts(): array { return ['fare_difference'=>'decimal:2']; }
    public function booking() { return $this->belongsTo(Booking::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function oldTrip() { return $this->belongsTo(Trip::class,'old_trip_id'); }
    public function newTrip() { return $this->belongsTo(Trip::class,'new_trip_id'); }
}
