@extends('layouts.app')
@section('title','My Wallet')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-brand-600 to-blue-800 rounded-2xl p-6 text-white mb-6">
        <p class="text-blue-200 text-sm">Available Balance</p>
        <p class="text-4xl font-black mt-1">₵{{ number_format($wallet->balance,2) }}</p>
        <p class="text-blue-200 text-sm mt-1">Ghana Cedi · GHS</p>
        <div class="mt-4 flex gap-3">
            <form method="POST" action="{{ route('passenger.wallet.fund') }}" x-data="{ amount:50 }" class="flex gap-2 flex-1">
                @csrf
                <input type="number" name="amount" x-model="amount" min="1" max="10000" step="0.01"
                       class="flex-1 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-xl px-3 py-2 text-white placeholder-blue-200 text-sm focus:outline-none focus:bg-opacity-30"
                       placeholder="Amount (GHS)">
                <button type="submit" class="bg-white text-brand-600 font-bold px-5 py-2 rounded-xl text-sm hover:bg-blue-50 transition">Top Up</button>
            </form>
        </div>
        <!-- Quick amounts -->
        <div class="flex gap-2 mt-3">
            @foreach([20,50,100,200] as $amt)
            <form method="POST" action="{{ route('passenger.wallet.fund') }}">
                @csrf <input type="hidden" name="amount" value="{{ $amt }}">
                <button type="submit" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs font-bold px-3 py-1 rounded-lg transition">₵{{ $amt }}</button>
            </form>
            @endforeach
        </div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-900">Transaction History</h2>
        </div>
        @if($transactions->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">No transactions yet.</div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($transactions as $tx)
                <div class="flex items-center gap-4 px-5 py-3.5">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $tx->type === 'credit' ? 'bg-green-100' : 'bg-red-100' }}">
                        <span class="text-base">{{ $tx->type === 'credit' ? '⬆️' : '⬇️' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $tx->description }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $tx->created_at->format('d M Y · H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-500' }}">
                            {{ $tx->type === 'credit' ? '+' : '-' }}₵{{ number_format($tx->amount,2) }}
                        </p>
                        <p class="text-xs text-gray-400">Bal: ₵{{ number_format($tx->balance_after,2) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
