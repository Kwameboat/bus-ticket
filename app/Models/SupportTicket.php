<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = ['user_id', 'subject', 'message', 'status', 'first_response_at'];

    protected function casts(): array
    {
        return ['first_response_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(SupportReply::class, 'ticket_id');
    }
}
