<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $fillable = ['booking_id','user_id','amount','currency','method','gateway','gateway_ref','gateway_access_code','status','webhook_payload','verification_response','meta','verified_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','webhook_payload'=>'array','verification_response'=>'array','meta'=>'array','verified_at'=>'datetime']; }
    public function booking() { return $this->belongsTo(Booking::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function refund()  { return $this->hasOne(Refund::class); }
    public function isSuccessful(): bool { return $this->status === 'success'; }
}
