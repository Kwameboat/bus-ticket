@extends('layouts.app')
@section('title','Search Results')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $originCity->name }} → {{ $destCity->name }}
            </h1>
            <p class="text-gray-500 text-sm mt-0.5">{{ $date->format('l, d F Y') }} · {{ $trips->count() }} {{ Str::plural('trip',$trips->count()) }} available</p>
        </div>
        <a href="{{ route('search') }}" class="ml-auto text-sm text-brand-600 hover:underline">Change Search</a>
    </div>

    @if($trips->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
            <div class="text-5xl mb-4">🚌</div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">No trips found</h3>
            <p class="text-gray-500 text-sm">No buses available on this route for {{ $date->format('d M Y') }}.</p>
            <a href="{{ route('search') }}" class="mt-4 inline-block bg-brand-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-brand-700 transition">Try Another Date</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($trips as $trip)
            <div class="bg-white rounded-2xl border border-gray-200 hover:border-brand-300 hover:shadow-md transition p-5">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <!-- Operator -->
                    <div class="flex items-center gap-3 md:w-40">
                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-gray-600">{{ substr($trip->operator->name,0,2) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $trip->operator->name }}</p>
                            <p class="text-xs text-gray-500">{{ $trip->bus->bus_type }}</p>
                        </div>
                    </div>

                    <!-- Times -->
                    <div class="flex-1 flex items-center gap-4">
                        <div class="text-center">
                            <p class="text-xl font-bold text-gray-900">{{ $trip->departs_at->format('H:i') }}</p>
                            <p class="text-xs text-gray-500">{{ $originCity->name }}</p>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="flex items-center gap-1">
                                <div class="flex-1 h-px bg-gray-300"></div>
                                <span class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">{{ $trip->route->duration_formatted }}</span>
                                <div class="flex-1 h-px bg-gray-300"></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-xl font-bold text-gray-900">{{ $trip->arrives_at->format('H:i') }}</p>
                            <p class="text-xs text-gray-500">{{ $destCity->name }}</p>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <div class="hidden md:flex gap-1 flex-wrap">
                        @foreach($trip->bus->amenities ?? [] as $am)
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $am }}</span>
                        @endforeach
                    </div>

                    <!-- Seats & Price -->
                    <div class="flex md:flex-col items-center md:items-end gap-4 md:gap-1">
                        <div class="text-right">
                            <p class="text-xs text-gray-500">From</p>
                            <p class="text-xl font-bold text-brand-600">
                                ₵{{ number_format(\App\Models\Fare::where('schedule_id',$trip->schedule_id)->min('base_fare') ?? 0, 2) }}
                            </p>
                        </div>
                        <div>
                            @if($trip->available_count > 0)
                                <span class="text-xs {{ $trip->available_count < 5 ? 'text-orange-600 bg-orange-50' : 'text-green-700 bg-green-50' }} px-2 py-0.5 rounded-full font-medium">
                                    {{ $trip->available_count }} seats left
                                </span>
                            @else
                                <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full font-medium">Fully Booked</span>
                            @endif
                        </div>
                        @if($trip->available_count > 0)
                            <a href="{{ route('passenger.trips.seats', $trip->id) }}"
                               class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2 rounded-xl text-sm transition whitespace-nowrap">
                                Select Seats
                            </a>
                        @elseif($trip->waitlist_enabled)
                            <a href="#" class="border border-brand-600 text-brand-600 font-bold px-5 py-2 rounded-xl text-sm hover:bg-brand-50 transition">
                                Join Waitlist
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Trip status badge -->
                @if($trip->status === 'boarding')
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <span class="text-xs font-semibold text-green-700 bg-green-50 px-2 py-1 rounded-full animate-pulse">🟢 Now Boarding</span>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
