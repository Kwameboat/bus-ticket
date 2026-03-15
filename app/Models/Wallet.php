<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model {
    protected $fillable = ['user_id','balance','currency','status'];
    protected function casts(): array { return ['balance'=>'decimal:2']; }
    public function user()         { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(WalletTransaction::class); }
    public function hasSufficientBalance(float $amount): bool { return $this->balance >= $amount && $this->status === 'active'; }
}
