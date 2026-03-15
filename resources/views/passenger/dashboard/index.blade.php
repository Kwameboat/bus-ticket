@extends('layouts.app')
@section('title','My Dashboard')
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-brand-600 to-blue-700 rounded-2xl p-6 text-white mb-6 flex items-center justify-between">
        <div>
            <p class="text-blue-200 text-sm">Welcome back</p>
            <h1 class="text-2xl font-black mt-0.5">{{ $user->name }}</h1>
            <p class="text-blue-200 text-sm mt-1">📍 Ghana</p>
        </div>
        <div class="text-right">
            <p class="text-blue-200 text-xs">Wallet Balance</p>
            <p class="text-3xl font-black">₵{{ number_format($walletBalance,2) }}</p>
            <a href="{{ route('passenger.wallet') }}" class="text-xs bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-1 rounded-full mt-1 inline-block transition">Top Up</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-black text-brand-600">{{ $totalBookings }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Trips</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-black text-green-600">₵{{ number_format($totalSpent,0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Spent</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-black text-blue-600">{{ $upcomingBookings->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Upcoming</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @foreach([
            [route('search'),'🔍','Search Buses','Find your next trip'],
            [route('passenger.bookings.index'),'🎫','My Tickets','View all bookings'],
            [route('passenger.wallet'),'💰','Wallet','Manage funds'],
            [route('passenger.ai.chat'),'🤖','AI Assistant','Get help'],
        ] as [$url,$icon,$label,$sub])
        <a href="{{ $url }}" class="bg-white border border-gray-200 hover:border-brand-300 hover:shadow-sm rounded-2xl p-4 text-center transition group">
            <div class="text-3xl mb-2">{{ $icon }}</div>
            <p class="font-bold text-gray-900 text-sm group-hover:text-brand-600">{{ $label }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $sub }}</p>
        </a>
        @endforeach
    </div>

    <!-- Upcoming Bookings -->
    @if($upcomingBookings->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-bold text-gray-900">Upcoming Trips</h2>
            <a href="{{ route('passenger.bookings.index') }}" class="text-sm text-brand-600 hover:underline">See all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($upcomingBookings as $booking)
            <a href="{{ route('passenger.bookings.show',$booking->booking_ref) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition">
                <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <span class="text-xl">🚌</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 text-sm">{{ $booking->trip->route->originCity->name }} → {{ $booking->trip->route->destinationCity->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $booking->trip->departs_at->format('D, d M Y · H:i') }} · {{ $booking->trip->operator->name }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="text-xs font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Confirmed</span>
                    <p class="text-sm font-bold text-gray-900 mt-1">₵{{ number_format($booking->grand_total,2) }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center mb-6">
        <div class="text-5xl mb-4">🚌</div>
        <h3 class="font-bold text-gray-900 mb-2">No upcoming trips</h3>
        <p class="text-gray-500 text-sm mb-4">Ready to travel? Search for buses across Ghana.</p>
        <a href="{{ route('search') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition">Search Buses</a>
    </div>
    @endif

    <!-- Notifications -->
    @if(auth()->user()->unreadNotifications->count())
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-900">Notifications</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach(auth()->user()->unreadNotifications->take(5) as $note)
            <div class="px-5 py-3 flex gap-3 items-start">
                <div class="w-2 h-2 bg-brand-500 rounded-full mt-1.5 flex-shrink-0"></div>
                <div>
                    <p class="text-sm text-gray-800">{{ $note->data['message'] ?? '' }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $note->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
