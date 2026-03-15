<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WaitlistEntry extends Model {
    protected $fillable = ['trip_id','user_id','boarding_point_id','dropoff_point_id','seats_requested','position','status','notified_at','claim_expires_at','claimed_at'];
    protected function casts(): array { return ['notified_at'=>'datetime','claim_expires_at'=>'datetime','claimed_at'=>'datetime']; }
    public function trip()          { return $this->belongsTo(Trip::class); }
    public function user()          { return $this->belongsTo(User::class); }
    public function boardingPoint() { return $this->belongsTo(Terminal::class,'boarding_point_id'); }
    public function dropoffPoint()  { return $this->belongsTo(Terminal::class,'dropoff_point_id'); }
    public function isClaimable(): bool {
        return $this->status === 'notified' && $this->claim_expires_at && now()->lte($this->claim_expires_at);
    }
}
