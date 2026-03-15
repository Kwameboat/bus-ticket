<?php
namespace App\Console\Commands;
use App\Models\Trip;
use Illuminate\Console\Command;

class UpdateTripStatus extends Command {
    protected $signature   = 'trips:update-status';
    protected $description = 'Auto-update trip statuses based on departure/arrival times';
    public function handle(): int {
        // Mark trips as in_transit (departed)
        $departed = Trip::where('status','scheduled')
            ->where('departs_at','<=',now())
            ->where('departs_at','>=',now()->subHours(12))
            ->update(['status'=>'in_transit','actual_departure'=>now()]);

        // Mark trips as arrived
        $arrived = Trip::where('status','in_transit')
            ->where('arrives_at','<=',now())
            ->update(['status'=>'arrived','actual_arrival'=>now()]);

        // Expire old QR tickets
        \App\Models\QrTicket::whereIn('status',['valid','generated'])
            ->whereHas('trip',fn($q)=>$q->where('arrives_at','<',now()->subHours(2)))
            ->update(['status'=>'expired']);

        // Expire pending bookings
        \App\Models\Booking::where('booking_status','pending')
            ->where('expires_at','<',now())
            ->each(function($b) {
                foreach ($b->seats as $s) {
                    $s->tripSeat?->update(['status'=>'available','locked_by_session'=>null,'locked_until'=>null]);
                }
                $b->update(['booking_status'=>'cancelled','cancellation_reason'=>'Payment timeout']);
            });

        $this->info("Departed: {$departed}, Arrived: {$arrived}");
        return Command::SUCCESS;
    }
}
