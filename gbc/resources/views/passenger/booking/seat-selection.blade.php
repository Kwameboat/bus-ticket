@extends('layouts.app')
@section('title','Select Seats')
@push('head')
<style>
.seat-btn { transition: all .15s; cursor: pointer; }
.seat-btn.available { @apply bg-white border-2 border-gray-300 hover:border-brand-500 hover:bg-brand-50 text-gray-700; }
.seat-btn.selected  { @apply bg-brand-600 border-2 border-brand-700 text-white shadow-md; }
.seat-btn.booked    { @apply bg-gray-200 border-2 border-gray-300 text-gray-400 cursor-not-allowed; }
.seat-btn.locked    { @apply bg-yellow-100 border-2 border-yellow-300 text-yellow-700 cursor-not-allowed; }
</style>
@endpush
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8" x-data="seatSelector()" x-init="init()">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('search') }}" class="hover:text-brand-600">Search</a>
        <span>/</span>
        <a href="{{ url()->previous() }}" class="hover:text-brand-600">Results</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">Select Seats</span>
    </div>

    <!-- Trip Info Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-6 flex flex-wrap gap-4 items-center">
        <div>
            <p class="text-xs text-gray-500">Route</p>
            <p class="font-bold text-gray-900">{{ $trip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Departure</p>
            <p class="font-bold text-gray-900">{{ $trip->departs_at->format('D, d M Y · H:i') }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Operator</p>
            <p class="font-bold text-gray-900">{{ $trip->operator->name }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Bus Type</p>
            <p class="font-bold text-gray-900">{{ $trip->bus->bus_type }}</p>
        </div>
        <!-- Lock countdown -->
        <div class="ml-auto" x-show="selectedSeats.length > 0">
            <p class="text-xs text-gray-500">Seat hold expires in</p>
            <p class="font-bold text-orange-600" x-text="countdown"></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Seat Map -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-gray-900">Bus Seat Layout</h2>
                    <div class="flex gap-3 text-xs text-gray-600">
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-white border-2 border-gray-300 inline-block"></span> Available</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-brand-600 inline-block"></span> Selected</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-200 inline-block"></span> Booked</span>
                    </div>
                </div>

                <!-- Bus front indicator -->
                <div class="text-center mb-4">
                    <div class="inline-block bg-gray-100 rounded-xl px-6 py-1.5 text-xs text-gray-500 font-medium">🚌 FRONT (DRIVER)</div>
                </div>

                <!-- Seat grid -->
                <div class="flex justify-center">
                    <div class="space-y-1.5">
                        @if($seatPlan && $seatPlan->layout)
                            @foreach($seatPlan->layout as $row)
                            <div class="flex gap-1.5">
                                @foreach($row as $cell)
                                    @if($cell === null || $cell === 'aisle')
                                        <div class="w-9 h-9"></div>
                                    @else
                                        @php $seatStatus = $availableSeats[$cell] ?? null; $status = $seatStatus?->status ?? 'available'; @endphp
                                        <button
                                            type="button"
                                            class="seat-btn w-9 h-9 rounded-lg text-xs font-semibold {{ $status }}"
                                            :class="selectedSeats.includes('{{ $cell }}') ? 'selected' : '{{ $status }}'"
                                            @if($status === 'booked') disabled @endif
                                            @if($status !== 'booked') @click="toggleSeat('{{ $cell }}')" @endif
                                            title="Seat {{ $cell }}">
                                            {{ $cell }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                            @endforeach
                        @else
                            <!-- Fallback grid if no seat plan -->
                            <p class="text-gray-500 text-sm text-center py-8">Seat map not available. Please proceed to checkout.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Selection Summary -->
        <div class="space-y-4">
            <!-- Boarding/Dropoff -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4">
                <h3 class="font-bold text-gray-900 mb-3">Journey Details</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Boarding Point</label>
                        <select id="boarding_point" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500"
                                x-model="boardingPointId" @change="updateFare()">
                            <option value="">Select boarding point</option>
                            @foreach($boardingPoints as $bp)
                                <option value="{{ $bp->terminal->id ?? $bp->id }}">{{ $bp->terminal->name ?? $bp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Drop-off Point</label>
                        <select id="dropoff_point" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500"
                                x-model="dropoffPointId" @change="updateFare()">
                            <option value="">Select drop-off point</option>
                            @foreach($boardingPoints as $bp)
                                <option value="{{ $bp->terminal->id ?? $bp->id }}">{{ $bp->terminal->name ?? $bp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Selected seats & fare -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4" x-show="selectedSeats.length > 0">
                <h3 class="font-bold text-gray-900 mb-3">Selected Seats</h3>
                <div class="space-y-1 mb-3">
                    <template x-for="seat in selectedSeats" :key="seat">
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium" x-text="'Seat ' + seat"></span>
                            <button @click="toggleSeat(seat)" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                        </div>
                    </template>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Seats selected</span>
                        <span x-text="selectedSeats.length"></span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 mt-1">
                        <span>Total</span>
                        <span class="text-brand-600" x-text="'₵' + (farePerSeat * selectedSeats.length).toFixed(2)"></span>
                    </div>
                </div>
                <button @click="proceedToCheckout()"
                        :disabled="selectedSeats.length === 0 || !boardingPointId || !dropoffPointId || locking"
                        class="mt-4 w-full bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition text-sm">
                    <span x-show="!locking">Continue to Checkout →</span>
                    <span x-show="locking" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Holding seats...
                    </span>
                </button>
            </div>

            <div x-show="selectedSeats.length === 0" class="bg-blue-50 rounded-xl p-4 text-sm text-blue-700 text-center">
                Click seats on the map to select them. You can select up to 6 seats.
            </div>
        </div>
    </div>

    <!-- Hidden form to proceed -->
    <form id="checkout-form" method="GET" action="{{ route('passenger.checkout') }}">
        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
        <input type="hidden" name="boarding_point_id" id="form-boarding">
        <input type="hidden" name="dropoff_point_id" id="form-dropoff">
        <div id="seat-inputs"></div>
    </form>
</div>

@push('scripts')
<script>
function seatSelector() {
    return {
        selectedSeats: [],
        boardingPointId: '',
        dropoffPointId: '',
        farePerSeat: 0,
        countdown: '12:00',
        locking: false,
        lockTimer: null,
        secondsLeft: 720,

        init() {
            this.startCountdown();
        },

        toggleSeat(seat) {
            if (this.selectedSeats.includes(seat)) {
                this.selectedSeats = this.selectedSeats.filter(s => s !== seat);
            } else {
                if (this.selectedSeats.length >= 6) { alert('You can select up to 6 seats.'); return; }
                this.selectedSeats.push(seat);
            }
        },

        updateFare() {
            if (!this.boardingPointId || !this.dropoffPointId) return;
            fetch(`/api/v1/fare?schedule_id={{ $trip->schedule_id }}&boarding=${this.boardingPointId}&dropoff=${this.dropoffPointId}`)
                .then(r => r.json())
                .then(d => this.farePerSeat = d.total || 0)
                .catch(() => {});
        },

        async proceedToCheckout() {
            if (!this.boardingPointId || !this.dropoffPointId) { alert('Please select boarding and drop-off points.'); return; }
            if (this.selectedSeats.length === 0) { alert('Please select at least one seat.'); return; }

            this.locking = true;
            try {
                const res = await fetch('{{ route("passenger.trips.lock", $trip->id) }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ seats: this.selectedSeats })
                });
                const data = await res.json();
                if (!data.success) { alert(data.message || 'Could not lock seats. Please try again.'); this.locking = false; return; }

                // Build form and submit
                document.getElementById('form-boarding').value = this.boardingPointId;
                document.getElementById('form-dropoff').value  = this.dropoffPointId;
                const container = document.getElementById('seat-inputs');
                container.innerHTML = '';
                this.selectedSeats.forEach(s => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = 'seats[]'; inp.value = s;
                    container.appendChild(inp);
                });
                document.getElementById('checkout-form').submit();
            } catch(e) {
                alert('Network error. Please try again.');
                this.locking = false;
            }
        },

        startCountdown() {
            setInterval(() => {
                if (this.selectedSeats.length === 0) { this.secondsLeft = 720; }
                if (this.secondsLeft <= 0) { alert('Seat hold expired. Please re-select.'); location.reload(); return; }
                this.secondsLeft--;
                const m = Math.floor(this.secondsLeft/60).toString().padStart(2,'0');
                const s = (this.secondsLeft%60).toString().padStart(2,'0');
                this.countdown = `${m}:${s}`;
            }, 1000);
        }
    }
}
</script>
@endpush
@endsection
