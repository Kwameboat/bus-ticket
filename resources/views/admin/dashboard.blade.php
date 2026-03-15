@extends('layouts.admin')
@section('title','Dashboard')
@section('content')

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $cards = [
        ['label'=>'Total Bookings','value'=>number_format($stats['total_bookings']),'icon'=>'🎫','color'=>'blue','sub'=>$stats['confirmed_bookings'].' confirmed'],
        ['label'=>'Total Revenue','value'=>'₵'.number_format($stats['total_revenue'],2),'icon'=>'💰','color'=>'green','sub'=>'₵'.number_format($stats['today_revenue'],2).' today'],
        ['label'=>'Passengers','value'=>number_format($stats['total_passengers']),'icon'=>'👥','color'=>'purple','sub'=>$stats['active_operators'].' operators'],
        ['label'=>'Pending Refunds','value'=>$stats['pending_refunds'],'icon'=>'🔄','color'=>'orange','sub'=>$stats['open_tickets'].' open tickets'],
    ];
    @endphp
    @foreach($cards as $card)
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
        <div class="flex items-start justify-between mb-3">
            <p class="text-gray-500 text-sm font-medium">{{ $card['label'] }}</p>
            <span class="text-2xl">{{ $card['icon'] }}</span>
        </div>
        <p class="text-2xl font-black text-gray-900">{{ $card['value'] }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $card['sub'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Revenue Chart -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-5">
        <h2 class="font-bold text-gray-900 mb-4">Revenue — Last 14 Days</h2>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    <!-- Top Routes -->
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
        <h2 class="font-bold text-gray-900 mb-4">Top Routes</h2>
        <div class="space-y-3">
            @forelse($topRoutes as $route)
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $route->name }}</p>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1">
                        <div class="bg-brand-600 h-1.5 rounded-full" style="width:{{ min(100, $route->cnt / ($topRoutes->max('cnt') ?: 1) * 100) }}%"></div>
                    </div>
                </div>
                <span class="text-sm font-bold text-gray-600 ml-3">{{ $route->cnt }}</span>
            </div>
            @empty
            <p class="text-gray-400 text-sm">No bookings yet.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-black text-gray-900">{{ $stats['today_trips'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Today's Trips</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-black text-gray-900">{{ $stats['total_routes'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Active Routes</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-black {{ $stats['pending_reviews'] > 0 ? 'text-orange-500' : 'text-gray-900' }}">{{ $stats['pending_reviews'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Reviews Pending</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-black {{ $stats['waitlist_entries'] > 0 ? 'text-blue-600' : 'text-gray-900' }}">{{ $stats['waitlist_entries'] }}</p>
        <p class="text-xs text-gray-500 mt-1">On Waitlist</p>
    </div>
</div>

<!-- Recent Bookings -->
<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900">Recent Bookings</h2>
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-brand-600 hover:underline">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Reference</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Passenger</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Route</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentBookings as $booking)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3 font-mono text-xs text-brand-600">
                        <a href="{{ route('admin.bookings.show',$booking->id) }}" class="hover:underline">{{ $booking->booking_ref }}</a>
                    </td>
                    <td class="px-5 py-3 text-gray-900">{{ $booking->user?->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $booking->trip?->route?->originCity?->name }} → {{ $booking->trip?->route?->destinationCity?->name }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $booking->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-900">₵{{ number_format($booking->grand_total,2) }}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $booking->booking_status === 'confirmed' ? 'bg-green-100 text-green-700' : ($booking->booking_status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($booking->booking_status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($revenueChart->pluck('date')->map(fn($d)=>\Carbon\Carbon::parse($d)->format('d M'))->toArray()),
        datasets: [{
            label: 'Revenue (₵)',
            data: @json($revenueChart->pluck('total')->toArray()),
            backgroundColor: 'rgba(26,86,219,0.7)',
            borderColor: 'rgba(26,86,219,1)',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '₵'+v.toLocaleString() } } }
    }
});
</script>
@endpush
@endsection
