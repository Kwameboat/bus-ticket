<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model {
    use SoftDeletes;
    protected $fillable = ['ticket_number','user_id','booking_id','subject','description','priority','status','category','assigned_to','resolved_at','first_response_at'];
    protected function casts(): array { return ['resolved_at'=>'datetime','first_response_at'=>'datetime']; }
    public function user()       { return $this->belongsTo(User::class); }
    public function booking()    { return $this->belongsTo(Booking::class); }
    public function assignedTo() { return $this->belongsTo(User::class,'assigned_to'); }
    public function replies()    { return $this->hasMany(SupportReply::class,'ticket_id')->orderBy('created_at'); }
    public function getPriorityColorAttribute(): string {
        return match($this->priority) { 'urgent'=>'red','high'=>'orange','medium'=>'yellow', default=>'gray' };
    }
}
