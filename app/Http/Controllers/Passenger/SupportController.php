<?php
namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\{SupportTicket, SupportReply, Faq};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id',Auth::id())->with('replies')->latest()->paginate(10);
        $faqs    = \App\Models\Faq::where('is_active',true)->orderBy('category')->orderBy('sort_order')->get()->groupBy('category');
        return view('passenger.support.index', compact('tickets','faqs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'    => 'required|string|max:300',
            'description'=> 'required|string|max:5000',
            'category'   => 'required|in:booking,payment,refund,technical,complaint,other',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        SupportTicket::create([
            'ticket_number' => 'TKT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'user_id'       => Auth::id(),
            'booking_id'    => $request->booking_id,
            'subject'       => $request->subject,
            'description'   => $request->description,
            'category'      => $request->category,
            'priority'      => 'medium',
            'status'        => 'open',
        ]);

        return redirect()->route('passenger.support.index')->with('success','Support ticket submitted. We\'ll respond within 24 hours.');
    }

    public function show(SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);
        $ticket->load('replies.user');
        return view('passenger.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);
        $request->validate(['message'=>'required|string|max:3000']);
        SupportReply::create(['ticket_id'=>$ticket->id,'user_id'=>Auth::id(),'message'=>$request->message,'is_staff_reply'=>false]);
        if ($ticket->status === 'resolved') $ticket->update(['status'=>'open']);
        return back()->with('success','Reply sent.');
    }
}
