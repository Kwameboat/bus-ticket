<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['route_id','bus_id','operator_id','driver_id','conductor_id','departure_time','arrival_time','recurrence_type','recurrence_days','valid_from','valid_to','waitlist_enabled','status','notes'];
    protected function casts(): array {
        return ['recurrence_days'=>'array','valid_from'=>'date','valid_to'=>'date','waitlist_enabled'=>'boolean'];
    }
    public function route()     { return $this->belongsTo(Route::class); }
    public function bus()       { return $this->belongsTo(Bus::class); }
    public function operator()  { return $this->belongsTo(Operator::class); }
    public function driver()    { return $this->belongsTo(Driver::class); }
    public function conductor() { return $this->belongsTo(Conductor::class); }
    public function fares()     { return $this->hasMany(Fare::class); }
    public function trips()     { return $this->hasMany(Trip::class); }
    public function scopeActive($q) { return $q->where('status','active'); }
}
