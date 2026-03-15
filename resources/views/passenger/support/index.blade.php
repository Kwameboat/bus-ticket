@extends('layouts.app')
@section('title','Support Center')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Support Center</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- New Ticket -->
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 class="font-bold text-gray-900 mb-4">Open a Support Ticket</h2>
            @if($errors->any())
                <div class="bg-red-50 rounded-xl p-3 mb-3">@foreach($errors->all() as $e)<p class="text-xs text-red-600">{{ $e }}</p>@endforeach</div>
            @endif
            <form method="POST" action="{{ route('passenger.support.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                    <select name="category" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500">
                        <option value="">Select...</option>
                        <option value="booking">Booking Issue</option>
                        <option value="payment">Payment Problem</option>
                        <option value="refund">Refund Request</option>
                        <option value="technical">Technical Issue</option>
                        <option value="complaint">Complaint</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                    <input type="text" name="subject" required maxlength="300" placeholder="Brief description of your issue"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <textarea name="description" required rows="4" maxlength="5000" placeholder="Please provide as much detail as possible..."
                              class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl text-sm transition">
                    Submit Ticket
                </button>
            </form>
        </div>

        <!-- FAQs -->
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 class="font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
            <div class="space-y-2" x-data="{ open: null }">
                @foreach($faqs->take(3) as $category => $items)
                    @foreach($items->take(3) as $i => $faq)
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <button @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                                class="w-full text-left px-4 py-3 text-sm font-medium text-gray-900 flex justify-between items-center hover:bg-gray-50">
                            <span>{{ $faq->question }}</span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition" :class="open === {{ $loop->index }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $loop->index }}" class="px-4 pb-3 text-xs text-gray-600 leading-relaxed">
                            {{ $faq->answer }}
                        </div>
                    </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <!-- Existing Tickets -->
    @if($tickets->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-bold text-gray-900">My Tickets</h2></div>
        <div class="divide-y divide-gray-50">
            @foreach($tickets as $ticket)
            <a href="{{ route('passenger.support.show',$ticket) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm truncate">{{ $ticket->subject }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $ticket->ticket_number }} · {{ $ticket->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0
                    {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-700' : ($ticket->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') }}">
                    {{ ucfirst(str_replace('_',' ',$ticket->status)) }}
                </span>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
