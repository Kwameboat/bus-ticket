<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BoardingSession extends Model {
    protected $fillable = ['trip_id','conductor_id','started_at','closed_at','total_scanned','total_boarded','invalid_scans','is_active','notes'];
    protected function casts(): array { return ['started_at'=>'datetime','closed_at'=>'datetime','is_active'=>'boolean']; }
    public function trip()      { return $this->belongsTo(Trip::class); }
    public function conductor() { return $this->belongsTo(User::class,'conductor_id'); }
}
