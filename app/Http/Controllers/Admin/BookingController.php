<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['user', 'schedule.route'])->latest()->paginate(20);
        return view('admin.bookings.index', compact('bookings'));
    }

    public function show($booking)
    {
        $booking = Booking::with(['user', 'schedule.route', 'schedule.bus'])->findOrFail($booking);
        return view('admin.bookings.show', compact('booking'));
    }

    public function cancel(Request $request, $booking)
    {
        $booking = Booking::findOrFail($booking);
        $booking->update(['status' => 'cancelled']);
        return back()->with('success', 'Booking cancelled.');
    }

    public function refunds()
    {
        $refunds = Booking::where('status', 'refund_requested')->with('user')->latest()->paginate(20);
        return view('admin.bookings.refunds', compact('refunds'));
    }

    public function approveRefund(Request $request, $refund)
    {
        $booking = Booking::findOrFail($refund);
        $booking->update(['status' => 'refunded']);
        return back()->with('success', 'Refund approved.');
    }

    public function rejectRefund(Request $request, $refund)
    {
        $booking = Booking::findOrFail($refund);
        $booking->update(['status' => 'confirmed']);
        return back()->with('success', 'Refund rejected.');
    }
}
