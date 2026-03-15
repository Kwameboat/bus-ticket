<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model {
    protected $fillable = ['wallet_id','user_id','type','amount','balance_before','balance_after','reference','description','source','booking_id','status','meta'];
    protected function casts(): array { return ['amount'=>'decimal:2','balance_before'=>'decimal:2','balance_after'=>'decimal:2','meta'=>'array']; }
    public function wallet()  { return $this->belongsTo(Wallet::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function booking() { return $this->belongsTo(Booking::class); }
}
