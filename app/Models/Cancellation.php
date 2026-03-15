<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model {
    protected $fillable = ['booking_id','user_id','reason','cancelled_by','refund_eligible','refund_amount','status','processed_by','processed_at'];
    protected function casts(): array { return ['refund_eligible'=>'boolean','refund_amount'=>'decimal:2','processed_at'=>'datetime']; }
    public function booking()     { return $this->belongsTo(Booking::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function processedBy() { return $this->belongsTo(User::class,'processed_by'); }
}
