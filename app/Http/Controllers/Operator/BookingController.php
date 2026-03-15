<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Booking;

class BookingController extends Controller
{
    public function index()
    {
        $operatorId = auth()->user()->operator?->id;
        $bookings = Booking::whereHas('schedule', fn($q) => $q->where('operator_id', $operatorId))
            ->with(['user', 'schedule.route'])->latest()->paginate(20);
        return view('operator.bookings', compact('bookings'));
    }
}
