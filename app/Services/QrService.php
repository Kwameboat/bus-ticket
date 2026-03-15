<?php
namespace App\Services;

use App\Models\{BookingSeat, Booking, QrTicket, TicketScan, Trip, BoardingSession};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class QrService
{
    private string $appKey;

    public function __construct()
    {
        $this->appKey = config('app.key');
    }

    /**
     * Generate a secure HMAC-signed QR ticket for a booking seat
     */
    public function generate(BookingSeat $bookingSeat, Booking $booking): QrTicket
    {
        $ticketNumber = $this->generateTicketNumber();

        // Build the payload (what gets encoded in QR)
        $payload = [
            'tn'  => $ticketNumber,          // ticket number
            'br'  => $booking->booking_ref,  // booking ref
            'tid' => $booking->trip_id,      // trip id
            'sl'  => $bookingSeat->seat_label,
            'ts'  => now()->timestamp,       // issued timestamp
        ];

        $payloadString  = implode(':', [$payload['tn'], $payload['br'], $payload['tid'], $payload['ts']]);
        $hmac           = hash_hmac('sha256', $payloadString, $this->appKey);
        $qrToken        = base64_encode(json_encode(['p' => $payload, 'h' => substr($hmac, 0, 32)]));
        $payloadHash    = hash('sha256', $payloadString);

        // Generate QR image using SimpleSoftwareIO/simple-qrcode
        $qrImagePath = null;
        try {
            $dir = 'qr-tickets/' . now()->format('Y/m');
            Storage::disk('public')->makeDirectory($dir);
            $filename = $dir . '/' . $ticketNumber . '.svg';
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)->errorCorrection('H')
                ->generate($qrToken);
            Storage::disk('public')->put($filename, $qrSvg);
            $qrImagePath = $filename;
        } catch (\Exception $e) {
            // QR image generation failure is non-fatal
        }

        $departs = $booking->trip->departs_at;

        return QrTicket::create([
            'booking_seat_id' => $bookingSeat->id,
            'booking_id'      => $booking->id,
            'trip_id'         => $booking->trip_id,
            'user_id'         => $booking->user_id,
            'ticket_number'   => $ticketNumber,
            'qr_token'        => $qrToken,
            'qr_payload_hash' => $payloadHash,
            'seat_label'      => $bookingSeat->seat_label,
            'passenger_name'  => $bookingSeat->passenger_name,
            'status'          => 'generated',
            'valid_from'      => $departs->copy()->subHours(2),
            'valid_until'     => $departs->copy()->addHours(4),
            'generated_at'    => now(),
            'qr_image_path'   => $qrImagePath,
        ]);
    }

    /**
     * Validate a QR token - returns result array
     */
    public function validate(string $qrToken, Trip $trip): array
    {
        try {
            $decoded = json_decode(base64_decode($qrToken), true);
            if (!$decoded || !isset($decoded['p'], $decoded['h'])) {
                return $this->scanResult('invalid', 'QR code is malformed');
            }

            $p      = $decoded['p'];
            $hmacIn = $decoded['h'];

            // Verify HMAC
            $payloadString    = implode(':', [$p['tn'], $p['br'], $p['tid'], $p['ts']]);
            $expectedHmac     = substr(hash_hmac('sha256', $payloadString, $this->appKey), 0, 32);

            if (!hash_equals($expectedHmac, $hmacIn)) {
                return $this->scanResult('invalid', 'QR code signature is invalid');
            }

            // Look up ticket
            $ticket = QrTicket::where('ticket_number', $p['tn'])->with(['booking.trip'])->first();
            if (!$ticket) {
                return $this->scanResult('invalid', 'Ticket not found');
            }

            // Wrong trip check
            if ((int)$p['tid'] !== $trip->id) {
                return $this->scanResult('wrong_trip', 'This ticket is for a different trip');
            }

            // Status checks
            return match($ticket->status) {
                'used'      => $this->scanResult('already_used', 'Passenger already boarded', $ticket),
                'cancelled' => $this->scanResult('cancelled', 'This ticket has been cancelled', $ticket),
                'refunded'  => $this->scanResult('refunded', 'This ticket was refunded', $ticket),
                'expired'   => $this->scanResult('expired', 'This ticket has expired', $ticket),
                'valid','generated' => $this->validateActive($ticket, $trip),
                default     => $this->scanResult('invalid', 'Unknown ticket status', $ticket),
            };
        } catch (\Exception $e) {
            return $this->scanResult('invalid', 'QR processing error');
        }
    }

    private function validateActive(QrTicket $ticket, Trip $trip): array
    {
        // Check if ticket status needs upgrading (generated → valid on trip day)
        if ($ticket->status === 'generated') {
            if ($trip->departs_at->isToday() || now()->gte($ticket->valid_from ?? $trip->departs_at->copy()->subHours(2))) {
                $ticket->update(['status' => 'valid']);
            } else {
                return $this->scanResult('invalid', 'Boarding not yet open for this trip', $ticket);
            }
        }
        return $this->scanResult('valid', 'Passenger may board', $ticket, true);
    }

    /**
     * Mark ticket as used after successful boarding
     */
    public function markBoarded(QrTicket $ticket, int $scannedBy, Trip $trip, string $method = 'qr_camera', ?string $deviceInfo = null): TicketScan
    {
        $ticket->update(['status' => 'used', 'used_at' => now()]);

        $scan = TicketScan::create([
            'qr_ticket_id'   => $ticket->id,
            'scanned_by'     => $scannedBy,
            'trip_id'        => $trip->id,
            'scan_method'    => $method,
            'scan_result'    => 'valid',
            'device_info'    => $deviceInfo,
            'ip_address'     => request()->ip(),
            'boarding_granted'=> true,
        ]);

        $trip->increment('boarded_count');

        // Update boarding session
        BoardingSession::where('trip_id', $trip->id)->where('is_active', true)
            ->increment('total_scanned')
            ->where('trip_id', $trip->id)->where('is_active', true)
            ->increment('total_boarded');

        return $scan;
    }

    /**
     * Validate ticket by manual ticket number entry (fallback)
     */
    public function validateByTicketNumber(string $ticketNumber, Trip $trip): array
    {
        $ticket = QrTicket::where('ticket_number', $ticketNumber)->first();
        if (!$ticket) return $this->scanResult('invalid', 'Ticket number not found');
        if ($ticket->trip_id !== $trip->id) return $this->scanResult('wrong_trip', 'Ticket is for a different trip', $ticket);
        return match($ticket->status) {
            'used'      => $this->scanResult('already_used', 'Already boarded', $ticket),
            'cancelled' => $this->scanResult('cancelled', 'Ticket cancelled', $ticket),
            'refunded'  => $this->scanResult('refunded', 'Ticket refunded', $ticket),
            'expired'   => $this->scanResult('expired', 'Ticket expired', $ticket),
            default     => $this->validateActive($ticket, $trip),
        };
    }

    private function scanResult(string $result, string $message, ?QrTicket $ticket = null, bool $granted = false): array
    {
        return [
            'result'           => $result,
            'message'          => $message,
            'boarding_granted' => $granted,
            'ticket'           => $ticket,
            'passenger_name'   => $ticket?->passenger_name,
            'seat_label'       => $ticket?->seat_label,
        ];
    }

    private function generateTicketNumber(): string
    {
        do { $tn = 'TKT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)); }
        while (QrTicket::where('ticket_number', $tn)->exists());
        return $tn;
    }

    public function activateForToday(): void
    {
        QrTicket::where('status','generated')
            ->whereHas('trip', fn($q)=>$q->whereDate('departs_at', today()))
            ->update(['status'=>'valid']);
    }

    public function expireOldTickets(): void
    {
        QrTicket::whereIn('status',['valid','generated'])
            ->whereHas('trip', fn($q)=>$q->where('departs_at','<', now()->subHours(4)))
            ->update(['status'=>'expired']);
    }
}
