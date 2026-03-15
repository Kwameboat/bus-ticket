@extends('layouts.app')
@section('title','Checkout')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="checkout()">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Complete Your Booking</h1>
    <form method="POST" action="{{ route('passenger.booking.store') }}" id="checkout-form">
        @csrf
        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
        <input type="hidden" name="boarding_point_id" value="{{ $boardingPoint->id }}">
        <input type="hidden" name="dropoff_point_id" value="{{ $dropoffPoint->id }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                <!-- Trip Summary -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-900 mb-3">Trip Details</h2>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-gray-500">Route</p><p class="font-semibold">{{ $trip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</p></div>
                        <div><p class="text-gray-500">Departure</p><p class="font-semibold">{{ $trip->departs_at->format('D, d M Y H:i') }}</p></div>
                        <div><p class="text-gray-500">Boarding At</p><p class="font-semibold">{{ $boardingPoint->name }}</p></div>
                        <div><p class="text-gray-500">Drop-off At</p><p class="font-semibold">{{ $dropoffPoint->name }}</p></div>
                        <div><p class="text-gray-500">Operator</p><p class="font-semibold">{{ $trip->operator->name }}</p></div>
                        <div><p class="text-gray-500">Bus</p><p class="font-semibold">{{ $trip->bus->name }} ({{ $trip->bus->bus_type }})</p></div>
                    </div>
                </div>

                <!-- Passenger Details -->
                @foreach($lockedSeats as $i => $seat)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-900 mb-4">Passenger {{ $i+1 }} — Seat {{ $seat->seat_label }}</h2>
                    <input type="hidden" name="passengers[{{ $i }}][seat]" value="{{ $seat->seat_number }}">
                    <input type="hidden" name="passengers[{{ $i }}][fare]" value="{{ $fare->total }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Full Name *</label>
                            <input type="text" name="passengers[{{ $i }}][name]"
                                   value="{{ $i === 0 ? auth()->user()->name : '' }}" required
                                   class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number</label>
                            <input type="tel" name="passengers[{{ $i }}][phone]"
                                   value="{{ $i === 0 ? auth()->user()->phone : '' }}"
                                   class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ID Type</label>
                            <select name="passengers[{{ $i }}][id_type]" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">
                                <option value="">Optional</option>
                                <option>Ghana Card</option><option>Passport</option><option>Voter ID</option><option>NHIS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ID Number</label>
                            <input type="text" name="passengers[{{ $i }}][id_number]"
                                   class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Promo Code -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5" x-data="{ code:'', applied:false, discount:0 }">
                    <h2 class="font-bold text-gray-900 mb-3">Promo Code</h2>
                    <div class="flex gap-2">
                        <input type="text" name="promo_code" x-model="code" placeholder="Enter promo code"
                               class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 uppercase">
                        <button type="button" @click="applyPromo()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-xl text-sm">Apply</button>
                    </div>
                    <p x-show="applied" class="text-green-600 text-xs mt-2 font-medium">✓ Promo code applied!</p>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-900 mb-4">Payment Method</h2>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-brand-300 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                            <input type="radio" name="payment_method" value="card" checked class="text-brand-600">
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">💳 Card / Bank Transfer</p>
                                <p class="text-xs text-gray-500">Visa, Mastercard, Bank transfer via Paystack</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-brand-300 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                            <input type="radio" name="payment_method" value="momo" class="text-brand-600">
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">📱 Mobile Money</p>
                                <p class="text-xs text-gray-500">MTN MoMo, Telecel Cash, AirtelTigo Money</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-brand-300 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 {{ $wallet->balance <= 0 ? 'opacity-50' : '' }}">
                            <input type="radio" name="payment_method" value="wallet" class="text-brand-600" {{ $wallet->balance <= 0 ? 'disabled' : '' }}>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">💰 GhanaBus Wallet</p>
                                <p class="text-xs text-gray-500">Balance: ₵{{ number_format($wallet->balance,2) }}</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-200 p-5 sticky top-20">
                    <h2 class="font-bold text-gray-900 mb-4">Order Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>{{ $lockedSeats->count() }} × Seat</span>
                            <span>₵{{ number_format($subtotal,2) }}</span>
                        </div>
                        @if($fare->tax_amount > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span>₵{{ number_format($fare->tax_amount * $lockedSeats->count(),2) }}</span>
                        </div>
                        @endif
                        @if($fare->service_charge > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Service Charge</span>
                            <span>₵{{ number_format($fare->service_charge * $lockedSeats->count(),2) }}</span>
                        </div>
                        @endif
                        <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-gray-900 text-base">
                            <span>Total</span>
                            <span class="text-brand-600">₵{{ number_format($subtotal + ($fare->tax_amount + $fare->service_charge)*$lockedSeats->count(),2) }}</span>
                        </div>
                    </div>
                    <button type="submit" class="mt-5 w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 rounded-xl transition">
                        Pay Now & Confirm →
                    </button>
                    <p class="text-xs text-gray-400 text-center mt-3">🔒 Secured by Paystack</p>
                </div>
            </div>
        </div>
    </form>
</div>
@push('scripts')
<script>
function checkout() {
    return {
        applyPromo() { /* Frontend validation placeholder */ this.applied = true; }
    }
}
</script>
@endpush
@endsection
