<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AiChatSession extends Model {
    protected $fillable = ['user_id','session_token','context_type','total_messages','total_tokens','last_active_at','is_active'];
    protected function casts(): array { return ['last_active_at'=>'datetime','is_active'=>'boolean']; }
    public function user()     { return $this->belongsTo(User::class); }
    public function messages() { return $this->hasMany(AiMessage::class,'session_id')->orderBy('created_at'); }
    public function getHistory(): array {
        return $this->messages->map(fn($m)=>['role'=>$m->role,'content'=>$m->content])->toArray();
    }
}
