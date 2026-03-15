@extends('layouts.app')
@section('title','Operator Dashboard')
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="w-14 h-14 bg-brand-100 rounded-2xl flex items-center justify-center text-2xl">🚌</div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $operator->name }}</h1>
            <p class="text-gray-500 text-sm">Operator Dashboard</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['Total Bookings', number_format($stats['total_bookings']), '🎫'],
            ["Today's Revenue", '₵'.number_format($stats['today_revenue'],2), '💰'],
            ["Today's Trips", $stats['today_trips'], '🗓️'],
            ['Active Buses', $stats['active_buses'], '🚌'],
        ] as [$label,$val,$icon])
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
            <div class="text-2xl mb-1">{{ $icon }}</div>
            <p class="text-xl font-black text-gray-900">{{ $val }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Today's Trips -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="font-bold text-gray-900">Today's Trips</h2>
                <a href="{{ route('operator.schedules') }}" class="text-sm text-brand-600 hover:underline">All Schedules</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($todayTrips as $trip)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $trip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</p>
                        <p class="text-xs text-gray-400">{{ $trip->departs_at->format('H:i') }} · {{ $trip->bus->name }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $trip->status === 'boarding' ? 'bg-green-100 text-green-700 animate-pulse' :
                               ($trip->status === 'in_transit' ? 'bg-blue-100 text-blue-700' :
                               ($trip->status === 'arrived' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700')) }}">
                            {{ ucfirst(str_replace('_',' ',$trip->status)) }}
                        </span>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $trip->boarded_count }}/{{ $trip->total_seats }} boarded</p>
                    </div>
                </div>
                @empty
                <p class="px-5 py-6 text-center text-gray-400 text-sm">No trips today.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="font-bold text-gray-900">Recent Bookings</h2>
                <a href="{{ route('operator.bookings') }}" class="text-sm text-brand-600 hover:underline">All Bookings</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentBookings as $booking)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 text-sm">{{ $booking->user?->name }}</p>
                        <p class="text-xs text-gray-400">{{ $booking->booking_ref }} · {{ $booking->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="font-semibold text-gray-900 text-sm">₵{{ number_format($booking->grand_total,2) }}</span>
                </div>
                @empty
                <p class="px-5 py-6 text-center text-gray-400 text-sm">No recent bookings.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
