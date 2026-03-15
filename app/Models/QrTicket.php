<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QrTicket extends Model {
    protected $fillable = ['booking_seat_id','booking_id','trip_id','user_id','ticket_number','qr_token','qr_payload_hash','seat_label','passenger_name','status','valid_from','valid_until','used_at','generated_at','qr_image_path'];
    protected function casts(): array { return ['valid_from'=>'datetime','valid_until'=>'datetime','used_at'=>'datetime','generated_at'=>'datetime']; }
    public function bookingSeat() { return $this->belongsTo(BookingSeat::class); }
    public function booking()     { return $this->belongsTo(Booking::class); }
    public function trip()        { return $this->belongsTo(Trip::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function scans()       { return $this->hasMany(TicketScan::class); }
    public function isValid(): bool {
        return $this->status === 'valid'
            && (!$this->valid_from || now()->gte($this->valid_from))
            && (!$this->valid_until || now()->lte($this->valid_until));
    }
    public function getQrImageUrlAttribute(): ?string {
        return $this->qr_image_path ? asset('storage/'.$this->qr_image_path) : null;
    }
}
