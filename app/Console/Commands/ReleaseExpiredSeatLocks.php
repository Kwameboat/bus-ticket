<?php
namespace App\Console\Commands;

use App\Models\TripSeat;
use Illuminate\Console\Command;

class ReleaseExpiredSeatLocks extends Command
{
    protected $signature   = 'bookings:release-locks';
    protected $description = 'Release expired seat locks to make seats available again';

    public function handle(): int
    {
        $count = TripSeat::where('status','locked')->where('locked_until','<',now())
            ->update(['status'=>'available','locked_by_session'=>null,'locked_by_user'=>null,'locked_until'=>null]);
        $this->info("Released {$count} expired seat locks.");
        return Command::SUCCESS;
    }
}
