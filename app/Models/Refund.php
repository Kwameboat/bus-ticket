<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model {
    protected $fillable = ['payment_id','booking_id','user_id','amount','currency','method','status','gateway_refund_ref','approved_by','approved_at','processed_at','admin_notes','reject_reason'];
    protected function casts(): array { return ['amount'=>'decimal:2','approved_at'=>'datetime','processed_at'=>'datetime']; }
    public function payment()    { return $this->belongsTo(Payment::class); }
    public function booking()    { return $this->belongsTo(Booking::class); }
    public function user()       { return $this->belongsTo(User::class); }
    public function approvedBy() { return $this->belongsTo(User::class,'approved_by'); }
}
