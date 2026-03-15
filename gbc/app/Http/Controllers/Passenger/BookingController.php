<?php
namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\{Trip, TripSeat, Fare, Terminal, Booking, QrTicket};
use App\Services\{BookingService, WalletService};
use App\Services\Payment\PaystackService;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, DB};
use Barryvdh\DomPDF\Facade\Pdf;

class BookingController extends Controller
{
    public function __construct(
        private BookingService  $bookingService,
        private PaystackService $paystackService,
        private WalletService   $walletService
    ) {}

    /** Show seat selection page */
    public function selectSeats(Trip $trip)
    {
        abort_if($trip->status === 'cancelled', 404);
        $trip->load(['bus.seatPlan','route.originCity','route.destinationCity','operator','seats']);

        $seatPlan       = $trip->bus->seatPlan;
        $boardingPoints = $trip->route->boardingPoints()->with('terminal.city')->get();
        $availableSeats = $trip->seats->keyBy('seat_number');

        return view('passenger.booking.seat-selection', compact('trip','seatPlan','boardingPoints','availableSeats'));
    }

    /** Lock seats via AJAX */
    public function lockSeats(Request $request, Trip $trip)
    {
        $request->validate([
            'seats'   => 'required|array|min:1|max:6',
            'seats.*' => 'string',
        ]);

        $result = $this->bookingService->lockSeats(
            $trip,
            $request->seats,
            session()->getId(),
            Auth::id()
        );

        if (!empty($result['failed'])) {
            return response()->json(['success'=>false,'message'=>'Some seats are no longer available: '.implode(', ',$result['failed']),'failed'=>$result['failed']], 409);
        }

        return response()->json(['success'=>true,'locked'=>$result['locked'],'expires_in'=>720]);
    }

    /** Show checkout form */
    public function checkout(Request $request)
    {
        $request->validate([
            'trip_id'           => 'required|exists:trips,id',
            'seats'             => 'required|array|min:1',
            'boarding_point_id' => 'required|exists:terminals,id',
            'dropoff_point_id'  => 'required|exists:terminals,id',
        ]);

        $trip = Trip::with(['bus','route','operator','seats'])->findOrFail($request->trip_id);
        $boardingPoint = Terminal::with('city')->findOrFail($request->boarding_point_id);
        $dropoffPoint  = Terminal::with('city')->findOrFail($request->dropoff_point_id);

        // Get fare for this segment
        $fare = Fare::where('schedule_id', $trip->schedule_id)
            ->where('boarding_point_id', $request->boarding_point_id)
            ->where('dropoff_point_id',  $request->dropoff_point_id)
            ->where('is_active', true)->first();

        if (!$fare) {
            return back()->with('error', 'Fare not found for this route segment.');
        }

        $lockedSeats = TripSeat::where('trip_id', $trip->id)
            ->whereIn('seat_number', $request->seats)
            ->where('locked_by_session', session()->getId())
            ->get();

        if ($lockedSeats->count() !== count($request->seats)) {
            return redirect()->route('trips.seats', $trip)->with('error', 'Seat lock expired. Please select seats again.');
        }

        $wallet  = Auth::user()->getOrCreateWallet();
        $subtotal = $fare->total * count($request->seats);

        return view('passenger.booking.checkout', compact(
            'trip','boardingPoint','dropoffPoint','fare','lockedSeats','wallet','subtotal'
        ));
    }

    /** Process checkout and create booking */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'trip_id'              => 'required|exists:trips,id',
            'boarding_point_id'    => 'required|exists:terminals,id',
            'dropoff_point_id'     => 'required|exists:terminals,id',
            'payment_method'       => 'required|in:card,momo,wallet',
            'promo_code'           => 'nullable|string|max:50',
            'passengers'           => 'required|array|min:1',
            'passengers.*.name'    => 'required|string|max:200',
            'passengers.*.seat'    => 'required|string',
            'passengers.*.fare'    => 'required|numeric|min:0',
        ]);

        $trip  = Trip::findOrFail($request->trip_id);
        $fare  = Fare::where('schedule_id', $trip->schedule_id)
            ->where('boarding_point_id', $request->boarding_point_id)
            ->where('dropoff_point_id', $request->dropoff_point_id)->first();

        // Build seat data
        $seats    = [];
        $subtotal = 0;
        foreach ($request->passengers as $i => $p) {
            $tripSeat = TripSeat::where('trip_id',$trip->id)->where('seat_number',$p['seat'])->first();
            if (!$tripSeat || !$tripSeat->isAvailable()) {
                return back()->with('error', "Seat {$p['seat']} is no longer available.");
            }
            $seats[] = [
                'seat_number'       => $p['seat'],
                'seat_label'        => $tripSeat->seat_label,
                'passenger_name'    => $p['name'],
                'passenger_phone'   => $p['phone'] ?? null,
                'passenger_id_type' => $p['id_type'] ?? null,
                'passenger_id_number'=> $p['id_number'] ?? null,
                'fare'              => $fare->total,
                'is_primary'        => $i === 0,
            ];
            $subtotal += $fare->total;
        }

        // Handle promo code
        $promoCodeId = null;
        if ($request->promo_code) {
            $promo = \App\Models\PromoCode::where('code', strtoupper($request->promo_code))->first();
            if ($promo && $promo->isValid($subtotal)) {
                $promoCodeId = $promo->id;
            }
        }

        try {
            $booking = $this->bookingService->createBooking([
                'user_id'           => Auth::id(),
                'trip_id'           => $trip->id,
                'boarding_point_id' => $request->boarding_point_id,
                'dropoff_point_id'  => $request->dropoff_point_id,
                'seats'             => $seats,
                'subtotal'          => $subtotal,
                'tax_amount'        => $fare->tax_amount * count($seats),
                'service_charge'    => $fare->service_charge * count($seats),
                'promo_code_id'     => $promoCodeId,
            ]);

            // Wallet payment
            if ($request->payment_method === 'wallet') {
                $wallet = Auth::user()->getOrCreateWallet();
                if (!$wallet->hasSufficientBalance($booking->grand_total)) {
                    $booking->delete();
                    return back()->with('error', 'Insufficient wallet balance.');
                }
                $this->walletService->debit(Auth::user(), $booking->grand_total, "Payment for booking {$booking->booking_ref}", $booking);
                $this->bookingService->confirm($booking);
                return redirect()->route('passenger.bookings.show', $booking->booking_ref)->with('success', 'Booking confirmed!');
            }

            // Paystack payment
            $payment = $this->paystackService->initializeTransaction($booking, $request->payment_method);
            session(['pending_booking_id' => $booking->id]);
            return redirect($payment['authorization_url']);

        } catch (\Exception $e) {
            return back()->with('error', 'Booking failed: '.$e->getMessage());
        }
    }

    /** Paystack callback */
    public function paymentCallback(Request $request): RedirectResponse
    {
        $reference = $request->get('reference') ?? $request->get('ref');
        if (!$reference) return redirect()->route('passenger.dashboard')->with('error', 'Invalid payment reference.');

        try {
            $verification = $this->paystackService->verify($reference);
            $payment = \App\Models\Payment::where('gateway_ref', $reference)->with('booking')->firstOrFail();

            if ($verification['success']) {
                $payment->update([
                    'status'                => 'success',
                    'verification_response' => $verification['data'],
                    'verified_at'           => now(),
                ]);
                $this->bookingService->confirm($payment->booking);
                return redirect()->route('passenger.bookings.show', $payment->booking->booking_ref)
                    ->with('success', '🎉 Booking confirmed! Your ticket is ready.');
            } else {
                $payment->update(['status'=>'failed','verification_response'=>$verification['data']]);
                return redirect()->route('passenger.dashboard')->with('error', 'Payment was not successful. Please try again.');
            }
        } catch (\Exception $e) {
            return redirect()->route('passenger.dashboard')->with('error', 'Payment verification failed.');
        }
    }

    /** Booking list */
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with(['trip.route.originCity','trip.route.destinationCity','trip.operator'])
            ->latest()->paginate(10);
        return view('passenger.booking.index', compact('bookings'));
    }

    /** Booking detail */
    public function show(string $ref)
    {
        $booking = Booking::where('booking_ref', $ref)
            ->where('user_id', Auth::id())
            ->with(['trip.route.originCity','trip.route.destinationCity','trip.bus','trip.operator','seats','qrTickets','boardingPoint','dropoffPoint','payments'])
            ->firstOrFail();
        return view('passenger.booking.show', compact('booking'));
    }

    /** Download PDF ticket */
    public function downloadTicket(string $ref)
    {
        $booking = Booking::where('booking_ref', $ref)->where('user_id', Auth::id())
            ->with(['trip.route.originCity','trip.route.destinationCity','trip.bus','trip.operator','seats.qrTicket','boardingPoint','dropoffPoint'])->firstOrFail();
        abort_unless($booking->isPaid(), 403, 'Ticket not available until payment is confirmed.');
        $pdf = Pdf::loadView('passenger.booking.ticket-pdf', compact('booking'))->setPaper('a4');
        return $pdf->download("ticket-{$booking->booking_ref}.pdf");
    }

    /** Cancel booking */
    public function cancel(Request $request, string $ref): RedirectResponse
    {
        $booking = Booking::where('booking_ref', $ref)->where('user_id', Auth::id())->firstOrFail();
        if (!$booking->isCancellable()) {
            return back()->with('error', 'This booking cannot be cancelled at this time.');
        }
        $this->bookingService->cancel($booking, $request->get('reason','Cancelled by passenger'));

        // Process refund if eligible
        $window = (int) setting('cancellation_window_hours', 4);
        if ($booking->trip->departs_at->diffInHours(now()) >= $window) {
            $refundAmount = $booking->grand_total * ((float)setting('cancellation_refund_percent',100)/100);
            app(\App\Services\WalletService::class)->refundToWallet(Auth::user(), $refundAmount, $booking);
        }

        return redirect()->route('passenger.bookings.index')->with('success', 'Booking cancelled successfully.');
    }
}
