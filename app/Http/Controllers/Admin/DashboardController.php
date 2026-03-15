<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Booking, Payment, Trip, User, Operator, Route, WaitlistEntry, Review, SupportTicket};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bookings'     => Booking::count(),
            'confirmed_bookings' => Booking::where('booking_status','confirmed')->count(),
            'total_revenue'      => Payment::where('status','success')->sum('amount'),
            'today_revenue'      => Payment::where('status','success')->whereDate('created_at',today())->sum('amount'),
            'total_passengers'   => User::role('passenger')->count(),
            'active_operators'   => Operator::where('status','active')->count(),
            'total_routes'       => Route::where('status','active')->count(),
            'pending_refunds'    => \App\Models\Refund::where('status','pending')->count(),
            'open_tickets'       => SupportTicket::where('status','open')->count(),
            'today_trips'        => Trip::whereDate('departs_at',today())->count(),
            'pending_reviews'    => Review::where('status','pending')->count(),
            'waitlist_entries'   => WaitlistEntry::where('status','waiting')->count(),
        ];

        // Revenue chart — last 14 days
        $revenueChart = Payment::where('status','success')
            ->where('created_at','>=',now()->subDays(14))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')->orderBy('date')->get();

        // Top routes by bookings
        $topRoutes = Booking::where('booking_status','confirmed')
            ->join('trips','bookings.trip_id','=','trips.id')
            ->join('routes','trips.route_id','=','routes.id')
            ->selectRaw('routes.name, COUNT(bookings.id) as cnt')
            ->groupBy('routes.id','routes.name')
            ->orderByDesc('cnt')->take(5)->get();

        // Recent bookings
        $recentBookings = Booking::with(['user','trip.route.originCity','trip.route.destinationCity'])
            ->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats','revenueChart','topRoutes','recentBookings'));
    }
}
