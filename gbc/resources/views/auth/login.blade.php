@extends('layouts.app')
@section('title','Sign In')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span class="text-white font-bold text-xl">GB</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-gray-500 mt-1">Sign in to your GhanaBus account</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4">
                    @foreach($errors->all() as $e)<p class="text-sm text-red-600">{{ $e }}</p>@endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-brand-600 hover:underline">Forgot password?</a>
                    </div>
                    <input type="password" name="password" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded">
                    <span class="text-sm text-gray-600">Keep me signed in</span>
                </label>
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 rounded-xl transition">
                    Sign In
                </button>
            </form>
            <p class="text-center text-sm text-gray-600 mt-6">
                Don't have an account? <a href="{{ route('register') }}" class="text-brand-600 font-semibold hover:underline">Sign up free</a>
            </p>
        </div>
    </div>
</div>
@endsection
