<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Booking;

class DashboardController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())->latest()->take(5)->get();
        return view('passenger.dashboard.index', compact('bookings'));
    }
}
