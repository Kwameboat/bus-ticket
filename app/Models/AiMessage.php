<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model {
    protected $fillable = ['session_id','user_id','role','content','provider','model','tokens_used','latency_ms','meta'];
    protected function casts(): array { return ['meta'=>'array']; }
    public function session() { return $this->belongsTo(AiChatSession::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
