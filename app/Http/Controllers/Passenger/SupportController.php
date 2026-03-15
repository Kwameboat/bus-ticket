<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportReply;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())->latest()->paginate(10);
        return view('passenger.support.index', compact('tickets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        SupportTicket::create([
            'user_id' => auth()->id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'status'  => 'open',
        ]);

        return back()->with('success', 'Support ticket created.');
    }

    public function show($ticket)
    {
        $ticket = SupportTicket::where('id', $ticket)->where('user_id', auth()->id())->with('replies.user')->firstOrFail();
        return view('passenger.support.show', compact('ticket'));
    }

    public function reply(Request $request, $ticket)
    {
        $request->validate(['message' => ['required', 'string']]);
        $supportTicket = SupportTicket::where('id', $ticket)->where('user_id', auth()->id())->firstOrFail();

        SupportReply::create([
            'ticket_id'      => $supportTicket->id,
            'user_id'        => auth()->id(),
            'message'        => $request->message,
            'is_staff_reply' => false,
        ]);

        return back()->with('success', 'Reply sent.');
    }
}
