<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Terminal extends Model {
    use HasFactory;
    protected $fillable = ['city_id','operator_id','name','address','lat','lng','phone','is_main_terminal','status'];
    protected function casts(): array { return ['is_main_terminal'=>'boolean','lat'=>'decimal:7','lng'=>'decimal:7']; }
    public function city()     { return $this->belongsTo(City::class); }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function scopeActive($q){ return $q->where('status','active'); }
}
