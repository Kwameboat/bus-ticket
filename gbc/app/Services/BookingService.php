<?php
namespace App\Services;

use App\Models\{Booking, BookingSeat, Trip, TripSeat, PromoCode};
use App\Models\{QrTicket, WaitlistEntry, AuditLog};
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private QrService     $qrService,
        private WalletService $walletService
    ) {}

    public function lockSeats(Trip $trip, array $seatNumbers, string $sessionId, int $userId): array
    {
        $locked = []; $failed = [];
        DB::transaction(function() use ($trip, $seatNumbers, $sessionId, $userId, &$locked, &$failed) {
            foreach ($seatNumbers as $seatNumber) {
                $seat = TripSeat::where('trip_id', $trip->id)
                    ->where('seat_number', $seatNumber)->lockForUpdate()->first();
                if (!$seat || !$seat->isAvailable()) { $failed[] = $seatNumber; continue; }
                $seat->update([
                    'status'            => 'locked',
                    'locked_by_session' => $sessionId,
                    'locked_by_user'    => $userId,
                    'locked_until'      => now()->addMinutes(12),
                ]);
                $locked[] = $seatNumber;
            }
        });
        return ['locked' => $locked, 'failed' => $failed];
    }

    public function releaseSessionLocks(string $sessionId): void
    {
        TripSeat::where('locked_by_session', $sessionId)->where('status', 'locked')
            ->update(['status'=>'available','locked_by_session'=>null,'locked_by_user'=>null,'locked_until'=>null]);
    }

    public function createBooking(array $data): Booking
    {
        return DB::transaction(function() use ($data) {
            $promoDiscount = 0;
            if (!empty($data['promo_code_id'])) {
                $promo = PromoCode::find($data['promo_code_id']);
                if ($promo && $promo->isValid($data['subtotal'])) {
                    $promoDiscount = $promo->calculateDiscount($data['subtotal']);
                    PromoCode::where('id', $data['promo_code_id'])->increment('used_count');
                }
            }

            $grand = $data['subtotal'] - $promoDiscount + ($data['tax_amount'] ?? 0) + ($data['service_charge'] ?? 0);

            $booking = Booking::create([
                'booking_ref'       => $this->generateRef(),
                'invoice_number'    => $this->generateInvoiceNumber(),
                'user_id'           => $data['user_id'],
                'trip_id'           => $data['trip_id'],
                'boarding_point_id' => $data['boarding_point_id'],
                'dropoff_point_id'  => $data['dropoff_point_id'],
                'promo_code_id'     => $data['promo_code_id'] ?? null,
                'seat_count'        => count($data['seats']),
                'subtotal'          => $data['subtotal'],
                'discount_amount'   => $promoDiscount,
                'tax_amount'        => $data['tax_amount'] ?? 0,
                'service_charge'    => $data['service_charge'] ?? 0,
                'grand_total'       => $grand,
                'currency'          => 'GHS',
                'payment_status'    => 'pending',
                'booking_status'    => 'pending',
                'expires_at'        => now()->addMinutes(30),
            ]);

            foreach ($data['seats'] as $i => $seat) {
                $tripSeat = TripSeat::where('trip_id', $data['trip_id'])
                    ->where('seat_number', $seat['seat_number'])->first();
                BookingSeat::create([
                    'booking_id'           => $booking->id,
                    'trip_seat_id'         => $tripSeat->id,
                    'seat_label'           => $seat['seat_label'],
                    'passenger_name'       => $seat['passenger_name'],
                    'passenger_phone'      => $seat['passenger_phone'] ?? null,
                    'passenger_id_type'    => $seat['passenger_id_type'] ?? null,
                    'passenger_id_number'  => $seat['passenger_id_number'] ?? null,
                    'fare'                 => $seat['fare'],
                    'is_primary_passenger' => $i === 0,
                ]);
            }
            return $booking;
        });
    }

    public function confirm(Booking $booking): Booking
    {
        return DB::transaction(function() use ($booking) {
            foreach ($booking->seats as $bookingSeat) {
                $bookingSeat->tripSeat->update([
                    'status'=>'booked','locked_by_session'=>null,'locked_by_user'=>null,'locked_until'=>null
                ]);
            }
            $booking->trip->decrement('available_seats', $booking->seat_count);
            $booking->update([
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
                'confirmed_at'   => now(),
            ]);
            foreach ($booking->seats as $bookingSeat) {
                $this->qrService->generate($bookingSeat, $booking);
            }
            try { $booking->user->notify(new BookingConfirmedNotification($booking)); } catch (\Exception $e) {}
            WaitlistEntry::where('trip_id', $booking->trip_id)->where('user_id', $booking->user_id)
                ->update(['status'=>'claimed','claimed_at'=>now()]);
            AuditLog::record('booking_confirmed', $booking);
            return $booking->fresh(['seats','trip','qrTickets']);
        });
    }

    public function cancel(Booking $booking, string $reason, string $cancelledBy = 'passenger'): bool
    {
        return DB::transaction(function() use ($booking, $reason, $cancelledBy) {
            foreach ($booking->seats as $seat) {
                $seat->tripSeat->update(['status'=>'available','locked_by_session'=>null,'locked_until'=>null]);
            }
            $booking->trip->increment('available_seats', $booking->seat_count);
            QrTicket::where('booking_id', $booking->id)->update(['status'=>'cancelled']);
            $booking->update(['booking_status'=>'cancelled','cancelled_at'=>now(),'cancellation_reason'=>$reason]);
            app(WaitlistService::class)->processAfterCancellation($booking->trip);
            AuditLog::record('booking_cancelled', $booking, [], ['reason'=>$reason,'by'=>$cancelledBy]);
            return true;
        });
    }

    private function generateRef(): string
    {
        do { $ref = 'GBC-'.strtoupper(Str::random(8)); }
        while (Booking::where('booking_ref', $ref)->exists());
        return $ref;
    }

    private function generateInvoiceNumber(): string
    {
        $count = Booking::whereYear('created_at', now()->year)->count() + 1;
        return 'INV-'.now()->format('Ym').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
