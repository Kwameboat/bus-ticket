@extends('layouts.app')
@section('title','Home')
@section('content')

<!-- Hero -->
<section class="bg-gradient-to-br from-brand-600 via-brand-700 to-blue-900 text-white">
    <div class="max-w-7xl mx-auto px-4 py-16 md:py-24">
        <div class="max-w-2xl">
            <h1 class="text-3xl md:text-5xl font-extrabold leading-tight mb-4">
                Book Bus Tickets<br>Across Ghana 🇬🇭
            </h1>
            <p class="text-blue-200 text-lg mb-8">Fast, secure, and convenient intercity travel. Search, select your seat, and pay with Paystack or Mobile Money.</p>

            <!-- Search Card -->
            <div class="bg-white rounded-2xl p-5 shadow-xl">
                <form action="{{ route('search.results') }}" method="POST" x-data="{ date: '{{ today()->addDay()->format('Y-m-d') }}' }">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">From</label>
                            <select name="origin" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                                <option value="">Select City</option>
                                @foreach(\App\Models\City::active()->orderBy('name')->get() as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">To</label>
                            <select name="destination" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                                <option value="">Select City</option>
                                @foreach(\App\Models\City::active()->orderBy('name')->get() as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Travel Date</label>
                            <input type="date" name="date" x-model="date" required min="{{ today()->format('Y-m-d') }}"
                                   class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl transition text-sm">
                                Search Buses
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Popular Routes -->
<section class="max-w-7xl mx-auto px-4 py-12">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Popular Routes</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach([['Accra','Kumasi'],['Accra','Takoradi'],['Accra','Cape Coast'],['Kumasi','Tamale'],['Accra','Ho'],['Accra','Sunyani']] as [$from,$to])
        <form method="POST" action="{{ route('search.results') }}">
            @csrf
            <input type="hidden" name="origin" value="{{ \App\Models\City::where('name',$from)->value('id') }}">
            <input type="hidden" name="destination" value="{{ \App\Models\City::where('name',$to)->value('id') }}">
            <input type="hidden" name="date" value="{{ today()->addDay()->format('Y-m-d') }}">
            <button type="submit" class="w-full bg-white hover:bg-brand-50 border border-gray-200 hover:border-brand-300 rounded-xl p-3 text-left transition group">
                <p class="text-xs text-gray-500 group-hover:text-brand-600">{{ $from }}</p>
                <p class="text-xs text-gray-400">↓</p>
                <p class="text-sm font-semibold text-gray-900 group-hover:text-brand-600">{{ $to }}</p>
            </button>
        </form>
        @endforeach
    </div>
</section>

<!-- How it works -->
<section class="bg-white py-12">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-900 text-center mb-10">How It Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach([
                ['🔍','Search','Enter your origin, destination, and travel date to see available buses.'],
                ['💺','Select Seat','Choose your preferred seat from the interactive bus layout.'],
                ['💳','Pay Securely','Pay with Paystack, MTN Mobile Money, or your GhanaBus wallet.'],
                ['🎫','Get Your Ticket','Receive a QR-coded ticket instantly via email and in-app.'],
            ] as [$icon,$title,$desc])
            <div class="text-center">
                <div class="text-4xl mb-3">{{ $icon }}</div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-600">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- PWA Install CTA -->
<section class="bg-brand-600 py-10">
    <div class="max-w-4xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6 text-white">
        <div>
            <h3 class="text-xl font-bold mb-1">Install the GhanaBus App</h3>
            <p class="text-blue-200 text-sm">Add to your home screen for a native app experience — no app store needed.</p>
        </div>
        <button onclick="installPWA()" id="hero-install-btn"
                class="bg-white text-brand-600 font-bold px-6 py-3 rounded-xl hover:bg-blue-50 transition whitespace-nowrap">
            📲 Install App
        </button>
    </div>
</section>

@endsection
