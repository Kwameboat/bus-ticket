<?php
namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $operator = Auth::user()->operator;
        abort_unless($operator, 403);

        $bookings = Booking::whereHas('trip', fn($q)=>$q->where('operator_id',$operator->id))
            ->with(['user','trip.route.originCity','trip.route.destinationCity','seats'])
            ->when($request->status, fn($q)=>$q->where('booking_status',$request->status))
            ->when($request->search, fn($q)=>$q->where('booking_ref','like',"%{$request->search}%"))
            ->latest()->paginate(20)->withQueryString();

        return view('operator.bookings.index', compact('bookings','operator'));
    }
}
