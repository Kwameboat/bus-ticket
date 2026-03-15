<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['booking_ref','invoice_number','user_id','trip_id','boarding_point_id','dropoff_point_id','promo_code_id','seat_count','subtotal','discount_amount','tax_amount','service_charge','grand_total','currency','payment_status','booking_status','payment_method','expires_at','confirmed_at','cancelled_at','cancellation_reason','is_reschedule','original_booking_id','meta'];
    protected function casts(): array {
        return ['expires_at'=>'datetime','confirmed_at'=>'datetime','cancelled_at'=>'datetime','is_reschedule'=>'boolean','meta'=>'array','grand_total'=>'decimal:2','subtotal'=>'decimal:2','discount_amount'=>'decimal:2','tax_amount'=>'decimal:2','service_charge'=>'decimal:2'];
    }
    public function user()          { return $this->belongsTo(User::class); }
    public function trip()          { return $this->belongsTo(Trip::class); }
    public function boardingPoint() { return $this->belongsTo(Terminal::class,'boarding_point_id'); }
    public function dropoffPoint()  { return $this->belongsTo(Terminal::class,'dropoff_point_id'); }
    public function promoCode()     { return $this->belongsTo(PromoCode::class); }
    public function seats()         { return $this->hasMany(BookingSeat::class); }
    public function payments()      { return $this->hasMany(Payment::class); }
    public function qrTickets()     { return $this->hasMany(QrTicket::class); }
    public function cancellation()  { return $this->hasOne(Cancellation::class); }
    public function reschedules()   { return $this->hasMany(Reschedule::class); }
    public function supportTickets(){ return $this->hasMany(SupportTicket::class); }
    public function review()        { return $this->hasOne(Review::class); }
    public function refunds()       { return $this->hasMany(Refund::class); }
    public function originalBooking(){ return $this->belongsTo(Booking::class,'original_booking_id'); }

    public function scopePaid($q)      { return $q->where('payment_status','paid'); }
    public function scopeConfirmed($q) { return $q->where('booking_status','confirmed'); }
    public function scopeUpcoming($q)  { return $q->whereHas('trip',fn($t)=>$t->where('departs_at','>',now())); }

    public function isPaid(): bool        { return $this->payment_status === 'paid'; }
    public function isConfirmed(): bool   { return $this->booking_status === 'confirmed'; }
    public function isCancellable(): bool {
        if (!$this->isConfirmed()) return false;
        $window = setting('cancellation_window_hours', 4);
        return $this->trip->departs_at->diffInHours(now()) >= $window;
    }

    public function getStatusBadgeAttribute(): string {
        return match($this->booking_status) {
            'confirmed'   => 'success',
            'pending'     => 'warning',
            'cancelled'   => 'danger',
            'completed'   => 'info',
            'no_show'     => 'secondary',
            'rescheduled' => 'primary',
            default       => 'secondary',
        };
    }
}
