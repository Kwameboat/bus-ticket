@extends('layouts.app')
@section('title','Offline')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-16 px-4">
    <div class="text-center max-w-sm">
        <div class="text-7xl mb-6">📵</div>
        <h1 class="text-2xl font-black text-gray-900 mb-3">You're Offline</h1>
        <p class="text-gray-500 mb-6">GhanaBus Connect needs an internet connection to search for buses and process bookings.</p>
        <button onclick="window.location.reload()" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition">
            Try Again
        </button>
    </div>
</div>
@endsection
