<?php
namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\{Booking, Trip, Payment};
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $operator = Auth::user()->operator;
        abort_unless($operator, 403, 'Operator account not found.');

        $stats = [
            'total_bookings'  => Booking::whereHas('trip', fn($q)=>$q->where('operator_id',$operator->id))->count(),
            'today_revenue'   => Payment::whereHas('booking.trip',fn($q)=>$q->where('operator_id',$operator->id))->where('status','success')->whereDate('created_at',today())->sum('amount'),
            'today_trips'     => Trip::where('operator_id',$operator->id)->whereDate('departs_at',today())->count(),
            'active_buses'    => $operator->buses()->where('status','active')->count(),
        ];

        $recentBookings = Booking::whereHas('trip',fn($q)=>$q->where('operator_id',$operator->id))
            ->with(['user','trip.route.originCity','trip.route.destinationCity'])
            ->latest()->take(10)->get();

        $todayTrips = Trip::where('operator_id',$operator->id)
            ->whereDate('departs_at',today())
            ->with(['route.originCity','route.destinationCity','bus'])
            ->orderBy('departs_at')->get();

        return view('operator.dashboard', compact('operator','stats','recentBookings','todayTrips'));
    }
}
