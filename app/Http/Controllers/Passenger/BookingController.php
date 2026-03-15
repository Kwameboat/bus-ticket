<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Seat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function selectSeats(Request $request, $trip)
    {
        $schedule = Schedule::with(['bus', 'route', 'seats'])->findOrFail($trip);
        return view('passenger.booking.seat-selection', compact('schedule'));
    }

    public function lockSeats(Request $request, $trip)
    {
        $schedule = Schedule::findOrFail($trip);
        return response()->json(['ok' => true]);
    }

    public function checkout(Request $request)
    {
        return view('passenger.booking.checkout');
    }

    public function store(Request $request)
    {
        $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'seats'       => ['required', 'array', 'min:1'],
        ]);

        $booking = Booking::create([
            'user_id'     => auth()->id(),
            'schedule_id' => $request->schedule_id,
            'reference'   => 'GBC-' . strtoupper(uniqid()),
            'status'      => 'pending',
            'total_amount'=> 0,
        ]);

        return redirect()->route('passenger.bookings.show', $booking->reference);
    }

    public function paymentCallback(Request $request)
    {
        return redirect()->route('passenger.bookings.index')->with('success', 'Payment processed.');
    }

    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())->latest()->paginate(10);
        return view('passenger.booking.index', compact('bookings'));
    }

    public function show($ref)
    {
        $booking = Booking::where('reference', $ref)->where('user_id', auth()->id())->firstOrFail();
        return view('passenger.booking.show', compact('booking'));
    }

    public function downloadTicket($ref)
    {
        $booking = Booking::where('reference', $ref)->where('user_id', auth()->id())->firstOrFail();
        $pdf = Pdf::loadView('passenger.booking.ticket-pdf', compact('booking'));
        return $pdf->download('ticket-'.$ref.'.pdf');
    }

    public function cancel(Request $request, $ref)
    {
        $booking = Booking::where('reference', $ref)->where('user_id', auth()->id())->firstOrFail();
        $booking->update(['status' => 'cancelled']);
        return back()->with('success', 'Booking cancelled.');
    }
}
