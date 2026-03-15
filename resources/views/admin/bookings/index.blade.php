@extends('layouts.admin')
@section('title','All Bookings')
@section('content')
<div class="flex gap-3 mb-6 items-end flex-wrap">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ref or passenger..."
               class="border border-gray-300 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-64">
        <select name="status" class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Statuses</option>
            @foreach(['pending','confirmed','cancelled','completed','rescheduled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}" class="border border-gray-300 rounded-xl px-3 py-2 text-sm">
        <button type="submit" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-xl text-sm">Filter</button>
        @if(request()->hasAny(['search','status','date']))
        <a href="{{ route('admin.bookings.index') }}" class="border border-gray-300 text-gray-600 font-semibold px-4 py-2 rounded-xl text-sm">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    @foreach(['Reference','Passenger','Route','Date','Seats','Amount','Status','Action'] as $h)
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs text-blue-600">
                        <a href="{{ route('admin.bookings.show',$booking->id) }}" class="hover:underline">{{ $booking->booking_ref }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $booking->user?->name }}</p>
                        <p class="text-xs text-gray-400">{{ $booking->user?->phone }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ $booking->trip?->route?->originCity?->name }} → {{ $booking->trip?->route?->destinationCity?->name }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $booking->trip?->departs_at?->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $booking->seat_count }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-900">₵{{ number_format($booking->grand_total,2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $booking->booking_status === 'confirmed' ? 'bg-green-100 text-green-700' :
                               ($booking->booking_status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($booking->booking_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.bookings.show',$booking->id) }}" class="text-blue-600 hover:underline text-xs font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">No bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
    <div class="px-5 py-3 border-t border-gray-100">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
