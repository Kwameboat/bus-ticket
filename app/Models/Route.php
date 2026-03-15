<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Route extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['origin_city_id','destination_city_id','origin_terminal_id','destination_terminal_id','name','slug','distance_km','duration_minutes','description','status'];
    public function originCity()        { return $this->belongsTo(City::class,'origin_city_id'); }
    public function destinationCity()   { return $this->belongsTo(City::class,'destination_city_id'); }
    public function originTerminal()    { return $this->belongsTo(Terminal::class,'origin_terminal_id'); }
    public function destinationTerminal(){ return $this->belongsTo(Terminal::class,'destination_terminal_id'); }
    public function boardingPoints()    { return $this->hasMany(RouteBoardingPoint::class)->orderBy('stop_order'); }
    public function schedules()         { return $this->hasMany(Schedule::class); }
    public function trips()             { return $this->hasMany(Trip::class); }
    public function scopeActive($q)     { return $q->where('status','active'); }
    public function getDurationFormattedAttribute(): string {
        if (!$this->duration_minutes) return 'N/A';
        $h = intdiv($this->duration_minutes,60);
        $m = $this->duration_minutes % 60;
        return $h.'h '.($m > 0 ? $m.'min' : '');
    }
}
