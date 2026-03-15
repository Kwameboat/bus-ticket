<?php
namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\{Booking, WalletTransaction};
use App\Services\WalletService;
use App\Services\Payment\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('wallet');
        $upcomingBookings = Booking::where('user_id', Auth::id())
            ->where('booking_status','confirmed')
            ->whereHas('trip', fn($q)=>$q->where('departs_at','>',now()))
            ->with(['trip.route.originCity','trip.route.destinationCity','trip.operator'])
            ->orderBy('created_at','desc')->take(3)->get();
        $totalBookings  = Booking::where('user_id',Auth::id())->count();
        $totalSpent     = Booking::where('user_id',Auth::id())->where('payment_status','paid')->sum('grand_total');
        $walletBalance  = $user->getOrCreateWallet()->balance;
        return view('passenger.dashboard.index', compact('user','upcomingBookings','totalBookings','totalSpent','walletBalance'));
    }
}
