<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TripSeat extends Model {
    protected $fillable = ['trip_id','seat_number','seat_label','status','locked_by_session','locked_by_user','locked_until'];
    protected function casts(): array { return ['locked_until'=>'datetime']; }
    public function trip()        { return $this->belongsTo(Trip::class); }
    public function lockedByUser(){ return $this->belongsTo(User::class,'locked_by_user'); }
    public function bookingSeat() { return $this->hasOne(BookingSeat::class); }

    public function isAvailable(): bool {
        if ($this->status === 'available') return true;
        if ($this->status === 'locked' && $this->locked_until && $this->locked_until->isPast()) return true;
        return false;
    }
    public function scopeAvailable($q) {
        return $q->where(function($q){
            $q->where('status','available')
              ->orWhere(function($q){ $q->where('status','locked')->where('locked_until','<',now()); });
        });
    }
}
