<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bookings' => Booking::count(),
            'total_users'    => User::count(),
            'revenue'        => Booking::where('status', 'confirmed')->sum('total_amount'),
        ];
        return view('admin.dashboard', compact('stats'));
    }
}
