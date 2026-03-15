<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['schedule_id','bus_id','route_id','operator_id','driver_id','conductor_id','trip_date','departs_at','arrives_at','status','available_seats','boarded_count','total_seats','actual_departure','actual_arrival','waitlist_enabled','cancellation_reason','notes'];
    protected function casts(): array {
        return ['trip_date'=>'date','departs_at'=>'datetime','arrives_at'=>'datetime','actual_departure'=>'datetime','actual_arrival'=>'datetime','waitlist_enabled'=>'boolean'];
    }
    public function schedule()    { return $this->belongsTo(Schedule::class); }
    public function bus()         { return $this->belongsTo(Bus::class); }
    public function route()       { return $this->belongsTo(Route::class); }
    public function operator()    { return $this->belongsTo(Operator::class); }
    public function driver()      { return $this->belongsTo(Driver::class); }
    public function conductor()   { return $this->belongsTo(Conductor::class); }
    public function seats()       { return $this->hasMany(TripSeat::class); }
    public function bookings()    { return $this->hasMany(Booking::class); }
    public function qrTickets()   { return $this->hasMany(QrTicket::class); }
    public function waitlist()    { return $this->hasMany(WaitlistEntry::class)->orderBy('position'); }
    public function scans()       { return $this->hasMany(TicketScan::class); }
    public function reviews()     { return $this->hasMany(Review::class); }

    public function scopeActive($q) { return $q->whereNotIn('status',['cancelled']); }
    public function scopeUpcoming($q) { return $q->where('departs_at','>',now()); }

    public function getOccupancyRateAttribute(): float {
        return $this->total_seats > 0
            ? round((($this->total_seats - $this->available_seats) / $this->total_seats) * 100, 1)
            : 0;
    }
    public function isFullyBooked(): bool { return $this->available_seats <= 0; }
}
