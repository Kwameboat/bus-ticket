<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1A56DB">
    <meta name="description" content="Book intercity bus tickets across Ghana — fast, easy, secure.">

    <title>@yield('title', 'GhanaBus Connect') — Book Bus Tickets in Ghana</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/pwa-icons/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="GhanaBus">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eff6ff',100:'#dbeafe',500:'#3b82f6',600:'#1A56DB',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                        accent: { 400:'#fb923c',500:'#F97316',600:'#ea580c' }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="h-full bg-gray-50 font-sans">

    <!-- Install Banner -->
    <div id="pwa-install-banner" class="hidden fixed top-0 left-0 right-0 z-50 bg-brand-600 text-white px-4 py-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <img src="/pwa-icons/icon-96.png" class="w-10 h-10 rounded-xl" alt="GhanaBus">
            <div>
                <p class="font-semibold text-sm">Install GhanaBus Connect</p>
                <p class="text-xs text-blue-200">Add to home screen for faster access</p>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="installPWA()" class="bg-white text-brand-600 text-xs font-bold px-3 py-1.5 rounded-lg">Install</button>
            <button onclick="dismissInstall()" class="text-blue-200 text-xs px-2">✕</button>
        </div>
    </div>

    <!-- iOS Install Banner -->
    <div id="ios-install-banner" class="hidden fixed bottom-4 left-4 right-4 z-50 bg-white rounded-2xl shadow-2xl border border-gray-200 p-4">
        <div class="flex items-start gap-3">
            <img src="/pwa-icons/icon-96.png" class="w-12 h-12 rounded-xl flex-shrink-0" alt="">
            <div class="flex-1">
                <p class="font-bold text-gray-900 text-sm">Install GhanaBus Connect</p>
                <p class="text-gray-600 text-xs mt-1">Tap <strong>Share</strong> <span class="inline-block">⬆️</span> then <strong>"Add to Home Screen"</strong> to install this app.</p>
            </div>
            <button onclick="dismissIosInstall()" class="text-gray-400 text-lg leading-none">✕</button>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">GB</span>
                        </div>
                        <span class="font-bold text-gray-900 text-lg hidden sm:block">GhanaBus Connect</span>
                    </a>
                </div>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('search') }}" class="text-gray-600 hover:text-brand-600 font-medium text-sm transition">Search Buses</a>
                    @auth
                        <a href="{{ route('passenger.dashboard') }}" class="text-gray-600 hover:text-brand-600 font-medium text-sm">Dashboard</a>
                        <a href="{{ route('passenger.bookings.index') }}" class="text-gray-600 hover:text-brand-600 font-medium text-sm">My Bookings</a>
                        <a href="{{ route('passenger.wallet') }}" class="text-gray-600 hover:text-brand-600 font-medium text-sm">
                            Wallet <span class="ml-1 text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">₵{{ number_format(auth()->user()->getOrCreateWallet()->balance, 2) }}</span>
                        </a>

                        <!-- Notifications bell -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open=!open" class="relative p-1.5 text-gray-500 hover:text-brand-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                                @endif
                            </button>
                            <div x-show="open" @click.away="open=false" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                                <div class="p-3 border-b border-gray-100 flex justify-between items-center">
                                    <span class="font-semibold text-sm text-gray-900">Notifications</span>
                                    <a href="#" class="text-xs text-brand-600">Mark all read</a>
                                </div>
                                <div class="max-h-72 overflow-y-auto">
                                    @forelse(auth()->user()->unreadNotifications->take(5) as $note)
                                        <div class="p-3 border-b border-gray-50 hover:bg-gray-50">
                                            <p class="text-xs text-gray-800">{{ $note->data['message'] ?? 'Notification' }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $note->created_at->diffForHumans() }}</p>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-gray-500 text-sm">No new notifications</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- User menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open=!open" class="flex items-center gap-2">
                                <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full object-cover" alt="">
                                <span class="text-sm font-medium text-gray-700 hidden lg:block">{{ Str::words(auth()->user()->name,1,'') }}</span>
                            </button>
                            <div x-show="open" @click.away="open=false" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                                <a href="{{ route('profile') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                                <a href="{{ route('passenger.ai.chat') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">AI Assistant</a>
                                <a href="{{ route('passenger.support.index') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Support</a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 text-sm text-brand-600 font-semibold hover:bg-gray-50">Admin Panel</a>
                                @endif
                                @if(auth()->user()->isConductor())
                                    <a href="{{ route('conductor.dashboard') }}" class="block px-4 py-2.5 text-sm text-brand-600 font-semibold hover:bg-gray-50">Conductor Panel</a>
                                @endif
                                <div class="border-t border-gray-100">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">Sign Out</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-brand-600 font-medium text-sm">Login</a>
                        <a href="{{ route('register') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-4 py-2 rounded-lg transition">Sign Up Free</a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="open=!open" class="p-2 text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="open" class="md:hidden border-t border-gray-200 bg-white px-4 py-3 space-y-2">
            <a href="{{ route('search') }}" class="block text-gray-700 font-medium py-2">🔍 Search Buses</a>
            @auth
                <a href="{{ route('passenger.dashboard') }}" class="block text-gray-700 py-2">📊 Dashboard</a>
                <a href="{{ route('passenger.bookings.index') }}" class="block text-gray-700 py-2">🎫 My Bookings</a>
                <a href="{{ route('passenger.wallet') }}" class="block text-gray-700 py-2">💰 Wallet</a>
                <a href="{{ route('passenger.ai.chat') }}" class="block text-gray-700 py-2">🤖 AI Assistant</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block text-brand-600 font-semibold py-2">⚙️ Admin Panel</a>
                @endif
                @if(auth()->user()->isConductor())
                    <a href="{{ route('conductor.dashboard') }}" class="block text-brand-600 font-semibold py-2">📱 Conductor Panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="block text-red-600 py-2">Sign Out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block text-gray-700 py-2">Login</a>
                <a href="{{ route('register') }}" class="block text-brand-600 font-bold py-2">Sign Up Free</a>
            @endauth
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,5000)"
             class="fixed top-20 right-4 z-50 bg-green-500 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 max-w-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="text-sm">{{ session('success') }}</span>
            <button @click="show=false" class="ml-auto text-green-200">✕</button>
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,6000)"
             class="fixed top-20 right-4 z-50 bg-red-500 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 max-w-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span class="text-sm">{{ session('error') }}</span>
            <button @click="show=false" class="ml-auto text-red-200">✕</button>
        </div>
    @endif

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Mobile Bottom Nav (shown in standalone PWA mode) -->
    @auth
    <nav class="pwa-only fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex z-30 shadow-lg">
        <a href="{{ route('home') }}" class="flex-1 flex flex-col items-center py-2 text-gray-500 hover:text-brand-600 {{ request()->is('/') ? 'text-brand-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="text-xs mt-0.5">Home</span>
        </a>
        <a href="{{ route('search') }}" class="flex-1 flex flex-col items-center py-2 text-gray-500 hover:text-brand-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="text-xs mt-0.5">Search</span>
        </a>
        <a href="{{ route('passenger.bookings.index') }}" class="flex-1 flex flex-col items-center py-2 text-gray-500 hover:text-brand-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            <span class="text-xs mt-0.5">Tickets</span>
        </a>
        <a href="{{ route('passenger.wallet') }}" class="flex-1 flex flex-col items-center py-2 text-gray-500 hover:text-brand-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span class="text-xs mt-0.5">Wallet</span>
        </a>
        <a href="{{ route('passenger.dashboard') }}" class="flex-1 flex flex-col items-center py-2 text-gray-500 hover:text-brand-600">
            <img src="{{ auth()->user()->avatar_url }}" class="w-5 h-5 rounded-full object-cover" alt="">
            <span class="text-xs mt-0.5">Me</span>
        </a>
    </nav>
    @endauth

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">GB</span>
                    </div>
                    <span class="text-white font-bold">GhanaBus Connect</span>
                </div>
                <p class="text-sm">Book intercity bus tickets across Ghana — fast, easy, and secure.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Travel</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('search') }}" class="hover:text-white transition">Search Buses</a></li>
                    <li><a href="#" class="hover:text-white transition">Popular Routes</a></li>
                    <li><a href="#" class="hover:text-white transition">Operators</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Support</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('passenger.support.index') }}" class="hover:text-white transition">Help Center</a></li>
                    <li><a href="/pages/faq" class="hover:text-white transition">FAQs</a></li>
                    <li><a href="/pages/refund-policy" class="hover:text-white transition">Refund Policy</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/pages/about" class="hover:text-white transition">About Us</a></li>
                    <li><a href="/pages/privacy" class="hover:text-white transition">Privacy Policy</a></li>
                    <li><a href="/pages/terms" class="hover:text-white transition">Terms of Use</a></li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 mt-8 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm">© {{ date('Y') }} GhanaBus Connect. All rights reserved.</p>
            <p class="text-sm">🇬🇭 Made for Ghana</p>
        </div>
    </footer>

    <!-- PWA Service Worker & Install Logic -->
    <script>
    // Register service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
            .then(reg => console.log('SW registered:', reg.scope))
            .catch(err => console.log('SW error:', err));
    }

    // PWA Install prompt
    let deferredPrompt;
    const INSTALL_KEY    = 'gbc_install_dismissed';
    const INSTALL_DELAY  = {{ \App\Models\Setting::get('pwa_install_delay', 3) * 1000 }};
    const RESHOW_DAYS    = {{ \App\Models\Setting::get('pwa_re_show_days', 7) }};

    const isStandalone   = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    const isIos          = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const dismissedAt    = localStorage.getItem(INSTALL_KEY);
    const daysSinceDismiss = dismissedAt ? (Date.now() - parseInt(dismissedAt)) / 86400000 : 999;

    if (!isStandalone && (!dismissedAt || daysSinceDismiss > RESHOW_DAYS)) {
        if (isIos) {
            setTimeout(() => document.getElementById('ios-install-banner')?.classList.remove('hidden'), INSTALL_DELAY);
        } else {
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                setTimeout(() => document.getElementById('pwa-install-banner')?.classList.remove('hidden'), INSTALL_DELAY);
            });
        }
    }

    function installPWA() {
        if (!deferredPrompt) return;
        document.getElementById('pwa-install-banner')?.classList.add('hidden');
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(choice => {
            if (choice.outcome === 'accepted') {
                localStorage.removeItem(INSTALL_KEY);
                fetch('/pwa/installed', { method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content} });
            }
            deferredPrompt = null;
        });
    }

    function dismissInstall() {
        localStorage.setItem(INSTALL_KEY, Date.now().toString());
        document.getElementById('pwa-install-banner')?.classList.add('hidden');
    }

    function dismissIosInstall() {
        localStorage.setItem(INSTALL_KEY, Date.now().toString());
        document.getElementById('ios-install-banner')?.classList.add('hidden');
    }

    // Standalone PWA: show bottom nav
    if (isStandalone) {
        document.querySelectorAll('.pwa-only').forEach(el => el.style.display = 'flex');
        document.body.style.paddingBottom = '64px';
    }

    window.addEventListener('appinstalled', () => {
        document.getElementById('pwa-install-banner')?.classList.add('hidden');
        localStorage.removeItem(INSTALL_KEY);
    });
    </script>

    @stack('scripts')
</body>
</html>
