<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportReply extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'message', 'is_staff_reply'];

    protected function casts(): array
    {
        return ['is_staff_reply' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
}
