<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['operator_id','name','license_number','phone','photo','id_number','license_expiry','status'];
    protected function casts(): array { return ['license_expiry'=>'date']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function trips()    { return $this->hasMany(Trip::class); }
}
