<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Operator extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id','name','slug','logo','reg_number','address','phone','email','commission_rate','description','status'];
    protected function casts(): array { return ['commission_rate'=>'decimal:2']; }
    public function user()       { return $this->belongsTo(User::class); }
    public function buses()      { return $this->hasMany(Bus::class); }
    public function schedules()  { return $this->hasMany(Schedule::class); }
    public function trips()      { return $this->hasMany(Trip::class); }
    public function drivers()    { return $this->hasMany(Driver::class); }
    public function conductors() { return $this->hasMany(Conductor::class); }
    public function reviews()    { return $this->hasMany(Review::class); }
    public function getLogoUrlAttribute(): string {
        return $this->logo ? asset('storage/'.$this->logo) : asset('images/default-operator.png');
    }
}
