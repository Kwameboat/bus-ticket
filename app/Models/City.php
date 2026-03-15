<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model {
    use HasFactory;
    protected $fillable = ['name','region','slug','lat','lng','is_popular','status'];
    protected function casts(): array { return ['is_popular'=>'boolean','lat'=>'decimal:7','lng'=>'decimal:7']; }
    public function terminals()            { return $this->hasMany(Terminal::class); }
    public function routesAsOrigin()       { return $this->hasMany(Route::class,'origin_city_id'); }
    public function routesAsDestination()  { return $this->hasMany(Route::class,'destination_city_id'); }
    public function scopeActive($q)        { return $q->where('status','active'); }
    public function scopePopular($q)       { return $q->where('is_popular',true); }
}
