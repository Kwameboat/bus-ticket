<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Release expired seat locks every 5 minutes
        $schedule->command('bookings:release-locks')->everyFiveMinutes();

        // Process waitlist every 10 minutes
        $schedule->command('waitlist:process')->everyTenMinutes();

        // Update trip statuses every 30 minutes
        $schedule->command('trips:update-status')->everyThirtyMinutes();

        // Send departure reminders daily at 6am
        $schedule->call(function() {
            $tomorrow = now()->addDay()->toDateString();
            \App\Models\Trip::whereDate('departs_at', $tomorrow)
                ->with(['bookings.user'])
                ->each(function($trip) {
                    foreach ($trip->bookings->where('booking_status','confirmed') as $booking) {
                        try {
                            $booking->user->notify(new \App\Notifications\TripReminderNotification($booking));
                        } catch (\Exception $e) {}
                    }
                });
        })->dailyAt('06:00')->name('send-trip-reminders')->withoutOverlapping();

        // Generate trips for next 7 days from active schedules - run daily at midnight
        $schedule->call(function() {
            \App\Models\Schedule::where('status','active')
                ->whereIn('recurrence_type',['daily','weekly','custom'])
                ->get()
                ->each(fn($s) => app(\App\Http\Controllers\Admin\FleetController::class)->generateTripsPublic($s, 7));
        })->dailyAt('00:05')->name('generate-upcoming-trips')->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
