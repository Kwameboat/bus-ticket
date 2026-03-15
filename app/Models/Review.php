<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Review extends Model {
    protected $fillable = ['user_id','trip_id','booking_id','operator_id','rating','body','status','moderated_by'];
    protected function casts(): array { return ['rating'=>'integer']; }
    public function user()        { return $this->belongsTo(User::class); }
    public function trip()        { return $this->belongsTo(Trip::class); }
    public function booking()     { return $this->belongsTo(Booking::class); }
    public function operator()    { return $this->belongsTo(Operator::class); }
    public function moderatedBy() { return $this->belongsTo(User::class,'moderated_by'); }
}
