<?php
namespace App\Services;

use App\Models\{WaitlistEntry, Trip, User};
use App\Notifications\WaitlistSeatAvailableNotification;
use Illuminate\Support\Facades\DB;

class WaitlistService
{
    public function join(Trip $trip, User $user, int $seatsRequested, int $boardingId, int $dropoffId): WaitlistEntry
    {
        $existing = WaitlistEntry::where('trip_id',$trip->id)->where('user_id',$user->id)->first();
        if ($existing) return $existing;

        $position = WaitlistEntry::where('trip_id',$trip->id)->whereIn('status',['waiting','notified'])->max('position') + 1;

        return WaitlistEntry::create([
            'trip_id'           => $trip->id,
            'user_id'           => $user->id,
            'boarding_point_id' => $boardingId,
            'dropoff_point_id'  => $dropoffId,
            'seats_requested'   => $seatsRequested,
            'position'          => $position,
            'status'            => 'waiting',
        ]);
    }

    public function processAfterCancellation(Trip $trip): void
    {
        $availableSeats = $trip->available_seats;
        if ($availableSeats <= 0) return;

        $entries = WaitlistEntry::where('trip_id',$trip->id)
            ->where('status','waiting')
            ->where('seats_requested','<=',$availableSeats)
            ->orderBy('position')
            ->get();

        foreach ($entries as $entry) {
            if ($entry->seats_requested <= $availableSeats) {
                $claimExpiry = now()->addMinutes((int)setting('waitlist_claim_minutes', 30));
                $entry->update([
                    'status'           => 'notified',
                    'notified_at'      => now(),
                    'claim_expires_at' => $claimExpiry,
                ]);
                try { $entry->user->notify(new WaitlistSeatAvailableNotification($entry, $trip)); } catch (\Exception $e) {}
                $availableSeats -= $entry->seats_requested;
                if ($availableSeats <= 0) break;
            }
        }
    }

    public function expireClaimWindows(): void
    {
        WaitlistEntry::where('status','notified')
            ->where('claim_expires_at','<',now())
            ->each(function($entry) {
                $entry->update(['status'=>'expired']);
                // Try next in queue
                $next = WaitlistEntry::where('trip_id',$entry->trip_id)
                    ->where('status','waiting')
                    ->where('seats_requested','<=',$entry->trip->available_seats)
                    ->orderBy('position')->first();
                if ($next) {
                    $next->update([
                        'status'           => 'notified',
                        'notified_at'      => now(),
                        'claim_expires_at' => now()->addMinutes((int)setting('waitlist_claim_minutes', 30)),
                    ]);
                    try { $next->user->notify(new WaitlistSeatAvailableNotification($next, $next->trip)); } catch (\Exception $e) {}
                }
            });
    }
}
