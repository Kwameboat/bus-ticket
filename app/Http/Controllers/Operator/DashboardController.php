<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Booking;
use App\Models\Schedule;

class DashboardController extends Controller
{
    public function index()
    {
        $operatorId = auth()->user()->operator?->id;
        $stats = [
            'buses'     => Bus::where('operator_id', $operatorId)->count(),
            'schedules' => Schedule::where('operator_id', $operatorId)->count(),
            'bookings'  => Booking::whereHas('schedule', fn($q) => $q->where('operator_id', $operatorId))->count(),
        ];
        return view('operator.dashboard', compact('stats'));
    }
}
