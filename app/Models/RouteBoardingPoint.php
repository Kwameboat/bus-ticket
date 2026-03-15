<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RouteBoardingPoint extends Model {
    protected $fillable = ['route_id','terminal_id','point_type','stop_order','fare_modifier','is_origin','is_destination'];
    protected function casts(): array { return ['is_origin'=>'boolean','is_destination'=>'boolean','fare_modifier'=>'decimal:2']; }
    public function route()    { return $this->belongsTo(Route::class); }
    public function terminal() { return $this->belongsTo(Terminal::class); }
}
