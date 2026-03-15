<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conductor extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['operator_id','user_id','name','staff_id','phone','photo','status'];
    public function operator()        { return $this->belongsTo(Operator::class); }
    public function user()            { return $this->belongsTo(User::class); }
    public function boardingSessions(){ return $this->hasMany(BoardingSession::class,'conductor_id'); }
}
