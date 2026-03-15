<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Trip, TripSeat, Fare, Booking, BookingSeat, Payment, QrTicket, WaitlistEntry};
use App\Services\{BookingService, QrService};
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $passengers = User::role('passenger')->get();
        $trips      = Trip::with(['route.originTerminal','route.destinationTerminal','seats','schedule'])
            ->whereDate('departs_at', '>=', today()->subDays(7))
            ->whereDate('departs_at', '<=', today()->addDays(5))
            ->get();

        if ($passengers->isEmpty() || $trips->isEmpty()) {
            $this->command->warn('No passengers or trips. Skipping bookings.');
            return;
        }

        $created = 0;
        foreach ($passengers->take(6) as $passenger) {
            $trip = $trips->random();
            $fare = Fare::where('schedule_id', $trip->schedule_id)->first();
            if (!$fare) continue;

            $availSeat = $trip->seats->where('status','available')->first();
            if (!$availSeat) continue;

            // Create booking
            $ref     = 'GBC-'.strtoupper(Str::random(8));
            $grand   = $fare->base_fare + $fare->service_charge;
            $booking = Booking::create([
                'booking_ref'       => $ref,
                'invoice_number'    => 'INV-'.now()->format('Ym').'-'.str_pad($created+1, 5,'0',STR_PAD_LEFT),
                'user_id'           => $passenger->id,
                'trip_id'           => $trip->id,
                'boarding_point_id' => $trip->route->origin_terminal_id ?? $trip->route->originTerminal?->id ?? 1,
                'dropoff_point_id'  => $trip->route->destination_terminal_id ?? $trip->route->destinationTerminal?->id ?? 2,
                'seat_count'        => 1,
                'subtotal'          => $fare->base_fare,
                'tax_amount'        => $fare->tax_amount,
                'service_charge'    => $fare->service_charge,
                'grand_total'       => $grand,
                'currency'          => 'GHS',
                'payment_status'    => 'paid',
                'booking_status'    => 'confirmed',
                'payment_method'    => 'card',
                'confirmed_at'      => now()->subHours(rand(1,48)),
            ]);

            // Booking seat
            $bookingSeat = BookingSeat::create([
                'booking_id'           => $booking->id,
                'trip_seat_id'         => $availSeat->id,
                'seat_label'           => $availSeat->seat_label,
                'passenger_name'       => $passenger->name,
                'passenger_phone'      => $passenger->phone,
                'fare'                 => $fare->base_fare,
                'is_primary_passenger' => true,
            ]);

            // Mark seat as booked
            $availSeat->update(['status'=>'booked']);
            $trip->decrement('available_seats');

            // Payment record
            Payment::create([
                'booking_id'  => $booking->id,
                'user_id'     => $passenger->id,
                'amount'      => $grand,
                'currency'    => 'GHS',
                'method'      => 'card',
                'gateway'     => 'paystack',
                'gateway_ref' => 'PSK-'.strtoupper(Str::random(16)),
                'status'      => 'success',
                'verified_at' => $booking->confirmed_at,
                'verification_response' => ['status'=>'success','gateway_response'=>'Approved'],
            ]);

            // QR Ticket
            $token   = base64_encode(json_encode([
                'p' => ['tn'=>'TKT-'.$ref,'br'=>$ref,'tid'=>$trip->id,'sl'=>$availSeat->seat_label,'ts'=>now()->timestamp],
                'h' => substr(hash_hmac('sha256', 'TKT-'.$ref.':'.$ref.':'.$trip->id.':'.now()->timestamp, config('app.key') ?: throw new \RuntimeException('APP_KEY is not set. Run: php artisan key:generate')), 0, 32),
            ]));

            QrTicket::create([
                'booking_seat_id' => $bookingSeat->id,
                'booking_id'      => $booking->id,
                'trip_id'         => $trip->id,
                'user_id'         => $passenger->id,
                'ticket_number'   => 'TKT-'.$ref,
                'qr_token'        => $token,
                'qr_payload_hash' => hash('sha256', 'TKT-'.$ref.':'.$ref.':'.$trip->id),
                'seat_label'      => $availSeat->seat_label,
                'passenger_name'  => $passenger->name,
                'status'          => $trip->departs_at->isPast() ? 'used' : 'valid',
                'valid_from'      => $trip->departs_at->copy()->subHours(2),
                'valid_until'     => $trip->departs_at->copy()->addHours(4),
                'generated_at'    => $booking->confirmed_at,
                'used_at'         => $trip->departs_at->isPast() ? $trip->departs_at->addMinutes(rand(5,60)) : null,
            ]);

            $created++;
        }

        // Waitlist entries for a fully-booked trip
        $fullTrip = Trip::where('available_seats', 0)->first();
        if ($fullTrip && $passengers->count() > 6) {
            foreach ($passengers->slice(6, 2) as $pos => $p) {
                WaitlistEntry::firstOrCreate(['trip_id'=>$fullTrip->id,'user_id'=>$p->id],[
                    'boarding_point_id' => $fullTrip->route->origin_terminal_id ?? 1,
                    'dropoff_point_id'  => $fullTrip->route->destination_terminal_id ?? 2,
                    'seats_requested'   => 1,
                    'position'          => $pos + 1,
                    'status'            => 'waiting',
                ]);
            }
        }

        $this->command->info("Bookings seeded: {$created} confirmed bookings with QR tickets.");
    }
}
