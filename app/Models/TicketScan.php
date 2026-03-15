<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TicketScan extends Model {
    protected $fillable = ['qr_ticket_id','scanned_by','trip_id','scan_method','scan_result','device_info','ip_address','boarding_granted','notes'];
    protected function casts(): array { return ['boarding_granted'=>'boolean']; }
    public function qrTicket()   { return $this->belongsTo(QrTicket::class); }
    public function scannedBy()  { return $this->belongsTo(User::class,'scanned_by'); }
    public function trip()       { return $this->belongsTo(Trip::class); }
}
