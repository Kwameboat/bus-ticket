<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model {
    protected $fillable = ['booking_id','trip_seat_id','seat_label','passenger_name','passenger_phone','passenger_id_type','passenger_id_number','fare','is_primary_passenger'];
    protected function casts(): array { return ['fare'=>'decimal:2','is_primary_passenger'=>'boolean']; }
    public function booking()  { return $this->belongsTo(Booking::class); }
    public function tripSeat() { return $this->belongsTo(TripSeat::class); }
    public function qrTicket() { return $this->hasOne(QrTicket::class); }
}
