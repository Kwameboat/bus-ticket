@extends('layouts.app')
@section('title','My Bookings')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Bookings</h1>

    @if($bookings->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
            <div class="text-5xl mb-4">🎫</div>
            <h3 class="font-bold text-gray-900 mb-2">No bookings yet</h3>
            <a href="{{ route('search') }}" class="bg-brand-600 text-white font-bold px-6 py-2.5 rounded-xl text-sm">Search Buses</a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($bookings as $booking)
            <a href="{{ route('passenger.bookings.show',$booking->booking_ref) }}"
               class="block bg-white rounded-2xl border border-gray-200 hover:border-brand-300 hover:shadow-sm transition p-5">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs text-brand-600 bg-brand-50 px-2 py-0.5 rounded-md">{{ $booking->booking_ref }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ $booking->booking_status === 'confirmed' ? 'bg-green-100 text-green-700' :
                                   ($booking->booking_status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($booking->booking_status) }}
                            </span>
                        </div>
                        <p class="font-bold text-gray-900">{{ $booking->trip->route->originCity->name }} → {{ $booking->trip->route->destinationCity->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $booking->trip->departs_at->format('D, d M Y · H:i') }} · {{ $booking->trip->operator->name }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-black text-gray-900">₵{{ number_format($booking->grand_total,2) }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $booking->seat_count }} {{ Str::plural('seat',$booking->seat_count) }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
