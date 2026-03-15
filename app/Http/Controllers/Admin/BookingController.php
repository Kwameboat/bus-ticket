<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Booking, Cancellation, Refund, Payment};
use App\Services\{BookingService, WalletService};
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService, private WalletService $walletService) {}

    public function index(Request $request)
    {
        $bookings = Booking::with(['user','trip.route.originCity','trip.route.destinationCity','trip.operator'])
            ->when($request->status,  fn($q)=>$q->where('booking_status',$request->status))
            ->when($request->search,  fn($q)=>$q->where('booking_ref','like',"%{$request->search}%")
                ->orWhereHas('user',fn($u)=>$u->where('name','like',"%{$request->search}%")))
            ->when($request->date,    fn($q)=>$q->whereDate('created_at',$request->date))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $booking->load(['user','trip.route','trip.bus','trip.operator','seats','payments','qrTickets','cancellation','refunds']);
        return view('admin.bookings.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking)
    {
        $request->validate(['reason'=>'required|string|max:500']);
        $this->bookingService->cancel($booking, $request->reason, 'admin');
        return back()->with('success','Booking cancelled.');
    }

    public function approveRefund(Request $request, Refund $refund)
    {
        $refund->update(['status'=>'approved','approved_by'=>auth()->id(),'approved_at'=>now(),'admin_notes'=>$request->notes]);
        if ($refund->method === 'wallet') {
            $this->walletService->refundToWallet($refund->user, $refund->amount, $refund->booking, 'Admin approved refund');
            $refund->update(['status'=>'processed','processed_at'=>now()]);
        }
        return back()->with('success','Refund approved.');
    }

    public function rejectRefund(Request $request, Refund $refund)
    {
        $request->validate(['reason'=>'required|string|max:500']);
        $refund->update(['status'=>'rejected','approved_by'=>auth()->id(),'approved_at'=>now(),'reject_reason'=>$request->reason]);
        return back()->with('success','Refund rejected.');
    }

    public function refunds(Request $request)
    {
        $refunds = Refund::with(['booking','user','payment'])
            ->when($request->status, fn($q)=>$q->where('status',$request->status))
            ->latest()->paginate(20);
        return view('admin.refunds.index', compact('refunds'));
    }
}
