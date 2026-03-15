<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name','email','phone','password','avatar','status',
        'id_type','id_number','dob','gender','address','city',
        'emergency_name','emergency_phone',
    ];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array {
        return ['email_verified_at'=>'datetime','dob'=>'date','password'=>'hashed'];
    }

    public function bookings()          { return $this->hasMany(Booking::class); }
    public function wallet()            { return $this->hasOne(Wallet::class); }
    public function walletTransactions(){ return $this->hasMany(WalletTransaction::class); }
    public function operator()          { return $this->hasOne(Operator::class); }
    public function conductor()         { return $this->hasOne(Conductor::class); }
    public function supportTickets()    { return $this->hasMany(SupportTicket::class); }
    public function reviews()           { return $this->hasMany(Review::class); }
    public function aiSessions()        { return $this->hasMany(AiChatSession::class); }
    public function auditLogs()         { return $this->hasMany(AuditLog::class); }

    public function isAdmin(): bool     { return $this->hasRole('super_admin'); }
    public function isOperator(): bool  { return $this->hasRole('operator'); }
    public function isPassenger(): bool { return $this->hasRole('passenger'); }
    public function isConductor(): bool { return $this->hasRole('conductor'); }
    public function isStaff(): bool     { return $this->hasRole('staff'); }

    public function getAvatarUrlAttribute(): string {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=1A56DB&color=fff&size=128';
    }

    public function getOrCreateWallet(): Wallet {
        return $this->wallet ?? Wallet::create(['user_id'=>$this->id]);
    }
}
