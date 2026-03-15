@extends('layouts.app')
@section('title','Booking '.$booking->booking_ref)
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Status Banner -->
    @if($booking->booking_status === 'confirmed')
    <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-6 flex items-center gap-3">
        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
            <p class="font-bold text-green-800">Booking Confirmed</p>
            <p class="text-sm text-green-600">Your ticket is ready. Show the QR code at boarding.</p>
        </div>
        <a href="{{ route('passenger.bookings.ticket', $booking->booking_ref) }}"
           class="ml-auto bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-2 rounded-xl">
            Download PDF
        </a>
    </div>
    @endif

    <!-- Main Ticket Card -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm mb-5">
        <!-- Ticket Header -->
        <div class="bg-brand-600 text-white p-5">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-200 text-xs font-medium uppercase tracking-wide">Booking Reference</p>
                    <p class="text-2xl font-black tracking-wider mt-1">{{ $booking->booking_ref }}</p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase
                        {{ $booking->booking_status === 'confirmed' ? 'bg-green-400 text-green-900' : 'bg-yellow-300 text-yellow-900' }}">
                        {{ $booking->booking_status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Route & Times -->
        <div class="p-5 border-b border-gray-100">
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-2xl font-black text-gray-900">{{ $trip->departs_at->format('H:i') }}</p>
                    <p class="text-sm text-gray-600 font-medium">{{ $booking->boardingPoint->name }}</p>
                    <p class="text-xs text-gray-400">{{ $booking->boardingPoint->city->name }}</p>
                </div>
                <div class="flex-1 text-center">
                    <div class="flex items-center gap-1">
                        <div class="flex-1 h-0.5 bg-gray-300"></div>
                        <span class="text-xs text-gray-400">{{ $trip->route->duration_formatted }}</span>
                        <div class="flex-1 h-0.5 bg-gray-300"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ $trip->departs_at->format('D, d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-black text-gray-900">{{ $trip->arrives_at->format('H:i') }}</p>
                    <p class="text-sm text-gray-600 font-medium">{{ $booking->dropoffPoint->name }}</p>
                    <p class="text-xs text-gray-400">{{ $booking->dropoffPoint->city->name }}</p>
                </div>
            </div>
        </div>

        <!-- Passenger & Seat Info -->
        <div class="p-5 border-b border-gray-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><p class="text-gray-500 text-xs">Operator</p><p class="font-semibold text-gray-900">{{ $trip->operator->name }}</p></div>
                <div><p class="text-gray-500 text-xs">Bus</p><p class="font-semibold text-gray-900">{{ $trip->bus->name }}</p></div>
                <div><p class="text-gray-500 text-xs">Seats</p><p class="font-semibold text-gray-900">{{ $booking->seats->pluck('seat_label')->join(', ') }}</p></div>
                <div><p class="text-gray-500 text-xs">Paid</p><p class="font-semibold text-green-600">₵{{ number_format($booking->grand_total,2) }}</p></div>
            </div>
        </div>

        <!-- QR Codes -->
        @if($booking->booking_status === 'confirmed' && $booking->qrTickets->isNotEmpty())
        <div class="p-5">
            <h3 class="font-bold text-gray-900 mb-4">Boarding QR Codes</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($booking->qrTickets as $qrTicket)
                <div class="text-center border border-gray-200 rounded-xl p-3">
                    @if($qrTicket->qr_image_path)
                        <img src="{{ asset('storage/'.$qrTicket->qr_image_path) }}" alt="QR Code" class="w-28 h-28 mx-auto mb-2">
                    @else
                        <div class="w-28 h-28 mx-auto mb-2 bg-gray-100 rounded-lg flex items-center justify-center">
                            <span class="text-xs text-gray-400">QR</span>
                        </div>
                    @endif
                    <p class="text-xs font-bold text-gray-900">Seat {{ $qrTicket->seat_label }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $qrTicket->passenger_name }}</p>
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full mt-1 font-medium
                        {{ $qrTicket->status === 'used' ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-700' }}">
                        {{ ucfirst($qrTicket->status) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('passenger.bookings.ticket', $booking->booking_ref) }}"
           class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition">
            📄 Download Ticket
        </a>
        @if($booking->isCancellable())
        <form method="POST" action="{{ route('passenger.bookings.cancel', $booking->booking_ref) }}"
              onsubmit="return confirm('Are you sure you want to cancel this booking?')">
            @csrf
            <input type="hidden" name="reason" value="Cancelled by passenger">
            <button type="submit" class="border border-red-300 text-red-600 hover:bg-red-50 font-semibold text-sm px-5 py-2.5 rounded-xl transition">
                Cancel Booking
            </button>
        </form>
        @endif
        <a href="{{ route('passenger.support.index') }}"
           class="border border-gray-300 text-gray-600 hover:bg-gray-50 font-semibold text-sm px-5 py-2.5 rounded-xl transition">
            Need Help?
        </a>
        <a href="{{ route('passenger.bookings.index') }}"
           class="text-brand-600 hover:underline font-semibold text-sm px-2 py-2.5">
            ← All Bookings
        </a>
    </div>
</div>
@endsection
