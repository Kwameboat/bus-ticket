<?php
namespace App\Console\Commands;
use App\Services\WaitlistService;
use App\Models\{Trip, QrTicket, Booking};
use Illuminate\Console\Command;

class ProcessWaitlist extends Command {
    protected $signature   = 'waitlist:process';
    protected $description = 'Process waitlist entries — expire claim windows and notify next passengers';
    public function handle(WaitlistService $ws): int {
        $ws->expireClaimWindows();
        $this->info('Waitlist processed.');
        return Command::SUCCESS;
    }
}
