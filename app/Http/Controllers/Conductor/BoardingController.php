<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;

class BoardingController extends Controller
{
    public function dashboard()
    {
        $schedules = Schedule::where('status', 'active')->with(['route', 'bus'])->get();
        return view('conductor.boarding-panel', compact('schedules'));
    }

    public function boardingPanel(Request $request, $trip)
    {
        $schedule = Schedule::with(['route', 'bus', 'bookings'])->findOrFail($trip);
        return view('conductor.boarding-panel', compact('schedule'));
    }

    public function scan(Request $request)
    {
        $request->validate(['qr_code' => ['required', 'string']]);
        $booking = Booking::where('reference', $request->qr_code)->first();

        if (!$booking) {
            return response()->json(['ok' => false, 'message' => 'Booking not found.'], 404);
        }

        $booking->update(['boarded' => true, 'boarded_at' => now()]);
        return response()->json(['ok' => true, 'message' => 'Passenger boarded successfully.']);
    }

    public function history(Request $request, $trip)
    {
        $schedule = Schedule::findOrFail($trip);
        $bookings = Booking::where('schedule_id', $schedule->id)->where('boarded', true)->with('user')->get();
        return view('conductor.boarding-panel', compact('schedule', 'bookings'));
    }
}
