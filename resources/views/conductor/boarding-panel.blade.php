@extends('layouts.app')
@section('title','Boarding — '.$trip->route->name ?? 'Trip')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-6" x-data="boardingScanner()" x-init="init()">

    <!-- Trip Info -->
    <div class="bg-brand-600 text-white rounded-2xl p-5 mb-5">
        <p class="text-blue-200 text-xs font-medium uppercase">Active Boarding Session</p>
        <p class="text-xl font-black mt-1">{{ $trip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</p>
        <p class="text-blue-200 text-sm mt-0.5">{{ $trip->departs_at->format('D, d M Y · H:i') }} · {{ $trip->operator->name }}</p>
        <div class="flex gap-4 mt-4 text-sm">
            <div class="bg-white bg-opacity-20 rounded-xl px-4 py-2 text-center">
                <p class="text-2xl font-black">{{ $stats['boarded'] }}</p>
                <p class="text-blue-200 text-xs">Boarded</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-xl px-4 py-2 text-center">
                <p class="text-2xl font-black">{{ $stats['remaining'] }}</p>
                <p class="text-blue-200 text-xs">Remaining</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-xl px-4 py-2 text-center">
                <p class="text-2xl font-black">{{ $stats['total_seats'] }}</p>
                <p class="text-blue-200 text-xs">Total Seats</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-xl px-4 py-2 text-center">
                <p class="text-2xl font-black">{{ $stats['invalid_scans'] }}</p>
                <p class="text-blue-200 text-xs">Invalid</p>
            </div>
        </div>
    </div>

    <!-- Scan Mode Toggle -->
    <div class="flex gap-2 mb-5">
        <button @click="mode='camera'" :class="mode==='camera' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="flex-1 py-2.5 rounded-xl font-semibold text-sm transition">📷 Camera Scan</button>
        <button @click="mode='manual'" :class="mode==='manual' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="flex-1 py-2.5 rounded-xl font-semibold text-sm transition">⌨️ Manual Entry</button>
    </div>

    <!-- Camera Scanner -->
    <div x-show="mode==='camera'" class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-5">
        <div class="relative">
            <div id="qr-reader" class="w-full" style="min-height:260px;"></div>
            <div x-show="!scanning" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-80">
                <button @click="startCamera()" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl">
                    📷 Start Camera
                </button>
            </div>
        </div>
        <!-- Scan Result -->
        <div x-show="lastResult" :class="{
            'bg-green-50 border-t-2 border-green-500': lastResult?.result === 'valid',
            'bg-yellow-50 border-t-2 border-yellow-400': lastResult?.result === 'already_used',
            'bg-red-50 border-t-2 border-red-500': lastResult && !['valid','already_used'].includes(lastResult.result)
        }" class="p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl" x-text="lastResult?.result === 'valid' ? '✅' : lastResult?.result === 'already_used' ? '⚠️' : '❌'"></div>
                <div>
                    <p class="font-bold text-gray-900 text-sm" x-text="lastResult?.message"></p>
                    <p class="text-xs text-gray-600 mt-0.5">
                        <span x-text="lastResult?.passenger_name"></span>
                        <span x-show="lastResult?.seat_label"> · Seat <span x-text="lastResult?.seat_label"></span></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Entry -->
    <div x-show="mode==='manual'" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5">
        <h3 class="font-bold text-gray-900 mb-3">Enter Ticket Number Manually</h3>
        <div class="flex gap-2">
            <input type="text" x-model="manualCode" placeholder="e.g. TKT-20240115-ABC123"
                   @keydown.enter="submitManual()"
                   class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-mono uppercase focus:ring-2 focus:ring-brand-500">
            <button @click="submitManual()" :disabled="scanning"
                    class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition">
                Verify
            </button>
        </div>
        <!-- Manual result -->
        <div x-show="lastResult" class="mt-3 p-3 rounded-xl" :class="{
            'bg-green-50 text-green-800': lastResult?.result === 'valid',
            'bg-yellow-50 text-yellow-800': lastResult?.result === 'already_used',
            'bg-red-50 text-red-800': lastResult && !['valid','already_used'].includes(lastResult.result)
        }">
            <p class="font-semibold text-sm" x-text="lastResult?.message"></p>
            <p class="text-xs mt-0.5" x-text="(lastResult?.passenger_name ?? '') + (lastResult?.seat_label ? ' · Seat ' + lastResult.seat_label : '')"></p>
        </div>
    </div>

    <!-- Recent Scans -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900 text-sm">Recent Scans</h3>
            <span class="text-xs text-gray-400">Auto-refreshes</span>
        </div>
        <div id="scan-list">
            @foreach($recentScans as $scan)
            <div class="px-5 py-3 border-b border-gray-50 flex items-center gap-3 last:border-0">
                <span class="text-xl">{{ $scan->boarding_granted ? '✅' : ($scan->scan_result === 'already_used' ? '⚠️' : '❌') }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $scan->qrTicket?->passenger_name ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-500">Seat {{ $scan->qrTicket?->seat_label }} · {{ $scan->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs {{ $scan->boarding_granted ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' }} px-2 py-0.5 rounded-full font-medium">
                    {{ ucfirst(str_replace('_',' ',$scan->scan_result)) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function boardingScanner() {
    return {
        mode: 'camera',
        scanning: false,
        lastResult: null,
        manualCode: '',
        html5Qr: null,
        tripId: {{ $trip->id }},
        csrfToken: document.querySelector('meta[name=csrf-token]').content,

        init() {
            // Auto-clear result after 4 seconds
            this.$watch('lastResult', (val) => {
                if (val) setTimeout(() => { if (this.lastResult === val) this.lastResult = null; }, 4000);
            });
        },

        async startCamera() {
            this.scanning = true;
            this.html5Qr = new Html5Qrcode('qr-reader');
            try {
                await this.html5Qr.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => this.processQR(decodedText),
                    () => {}
                );
            } catch(e) {
                this.scanning = false;
                alert('Camera access denied. Please use manual entry.');
                this.mode = 'manual';
            }
        },

        async processQR(qrToken) {
            if (this.lastResult) return; // Debounce
            const result = await this.sendScan({ qr_token: qrToken, method: 'qr_camera' });
            this.lastResult = result;
            // Haptic feedback
            if (navigator.vibrate) navigator.vibrate(result.boarding_granted ? [50] : [100,50,100]);
        },

        async submitManual() {
            if (!this.manualCode.trim()) return;
            const result = await this.sendScan({ ticket_number: this.manualCode.trim(), method: 'qr_manual' });
            this.lastResult = result;
            if (result.boarding_granted) this.manualCode = '';
        },

        async sendScan(payload) {
            try {
                const res = await fetch('{{ route("conductor.scan") }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify({ ...payload, trip_id: this.tripId })
                });
                return await res.json();
            } catch(e) {
                return { result: 'invalid', message: 'Network error. Please try again.', boarding_granted: false };
            }
        }
    }
}
</script>
@endpush
@endsection
