<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Passenger\{SearchController,BookingController,DashboardController,WalletController,AiChatController,SupportController};
use App\Http\Controllers\Conductor\BoardingController;
use App\Http\Controllers\Admin\{DashboardController as AdminDashboard,BookingController as AdminBooking,FleetController,SettingsController};
use App\Http\Controllers\API\WebhookController;

// ── Public routes ─────────────────────────────────────────────────────────────
Route::get('/', fn() => view('home'))->name('home');
Route::get('/search', [SearchController::class,'index'])->name('search');
Route::post('/search', [SearchController::class,'results'])->name('search.results');
Route::get('/offline', fn() => view('pwa.offline'))->name('offline');
Route::get('/pages/{slug}', fn($slug) => view('page', ['page' => \App\Models\Page::where('slug',$slug)->where('is_active',true)->firstOrFail()]))->name('page');

// PWA install tracking
Route::post('/pwa/installed', fn() => response()->json(['ok'=>true]))->name('pwa.installed');

// ── Auth routes ───────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function() {
    Route::get('/register',  [RegisterController::class,'create'])->name('register');
    Route::post('/register', [RegisterController::class,'store']);
    Route::get('/login',  fn() => view('auth.login'))->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class,'authenticate'])->name('login.post');
    Route::get('/forgot-password',  fn() => view('auth.forgot-password'))->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class,'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}',  fn($token) => view('auth.reset-password',['token'=>$token]))->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetController::class,'reset'])->name('password.update');
});

Route::middleware('auth')->group(function() {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class,'logout'])->name('logout');
    Route::get('/email/verify', fn() => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\EmailVerificationController::class,'verify'])
        ->middleware(['signed','throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [App\Http\Controllers\Auth\EmailVerificationController::class,'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
    Route::get('/profile', fn() => view('auth.profile',['user'=>auth()->user()]))->name('profile');
    Route::put('/profile', [App\Http\Controllers\Auth\ProfileController::class,'update'])->name('profile.update');
});

// ── Passenger routes ──────────────────────────────────────────────────────────
Route::middleware(['auth','verified','role:passenger|super_admin'])->prefix('')->name('passenger.')->group(function() {
    Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');

    // Trip search & seat selection
    Route::get('/trips/{trip}/seats', [BookingController::class,'selectSeats'])->name('trips.seats');
    Route::post('/trips/{trip}/seats/lock', [BookingController::class,'lockSeats'])->name('trips.lock');

    // Booking flow
    Route::get('/checkout',  [BookingController::class,'checkout'])->name('checkout');
    Route::post('/checkout', [BookingController::class,'store'])->name('booking.store');
    Route::get('/payment/callback', [BookingController::class,'paymentCallback'])->name('payment.callback');

    // Booking management
    Route::get('/bookings',              [BookingController::class,'index'])->name('bookings.index');
    Route::get('/bookings/{ref}',        [BookingController::class,'show'])->name('bookings.show');
    Route::get('/bookings/{ref}/ticket', [BookingController::class,'downloadTicket'])->name('bookings.ticket');
    Route::post('/bookings/{ref}/cancel',[BookingController::class,'cancel'])->name('bookings.cancel');

    // Wallet
    Route::get('/wallet',                [WalletController::class,'index'])->name('wallet');
    Route::post('/wallet/fund',          [WalletController::class,'fund'])->name('wallet.fund');
    Route::get('/wallet/fund/callback',  [WalletController::class,'fundCallback'])->name('wallet.fund.callback');

    // AI Chat
    Route::get('/ai/chat',   [AiChatController::class,'index'])->name('ai.chat');
    Route::post('/ai/send',  [AiChatController::class,'send'])->name('ai.send');

    // Support
    Route::get('/support',              [SupportController::class,'index'])->name('support.index');
    Route::post('/support',             [SupportController::class,'store'])->name('support.store');
    Route::get('/support/{ticket}',     [SupportController::class,'show'])->name('support.show');
    Route::post('/support/{ticket}/reply',[SupportController::class,'reply'])->name('support.reply');
});

// ── Conductor routes ──────────────────────────────────────────────────────────
Route::middleware(['auth','role:conductor|staff|super_admin'])->prefix('conductor')->name('conductor.')->group(function() {
    Route::get('/',                          [BoardingController::class,'dashboard'])->name('dashboard');
    Route::get('/trips/{trip}/boarding',     [BoardingController::class,'boardingPanel'])->name('boarding');
    Route::post('/scan',                     [BoardingController::class,'scan'])->name('scan');
    Route::get('/trips/{trip}/scan-history', [BoardingController::class,'history'])->name('history');
});

// ── Operator routes ───────────────────────────────────────────────────────────
Route::middleware(['auth','role:operator|super_admin'])->prefix('operator')->name('operator.')->group(function() {
    Route::get('/dashboard', [App\Http\Controllers\Operator\DashboardController::class,'index'])->name('dashboard');
    Route::get('/bookings',  [App\Http\Controllers\Operator\BookingController::class,'index'])->name('bookings');
    Route::get('/buses',     fn()=>view('operator.buses.index',['buses'=>\App\Models\Bus::where('operator_id',auth()->user()->operator?->id)->paginate(20)]))->name('buses');
    Route::get('/schedules', fn()=>view('operator.schedules.index',['schedules'=>\App\Models\Schedule::where('operator_id',auth()->user()->operator?->id)->with('route','bus')->paginate(20)]))->name('schedules');
});

// ── Admin routes ──────────────────────────────────────────────────────────────
Route::middleware(['auth','role:super_admin'])->prefix('admin')->name('admin.')->group(function() {
    Route::get('/',         [AdminDashboard::class,'index'])->name('dashboard');

    // Bookings & payments
    Route::get('/bookings',                 [AdminBooking::class,'index'])->name('bookings.index');
    Route::get('/bookings/{booking}',       [AdminBooking::class,'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel',[AdminBooking::class,'cancel'])->name('bookings.cancel');
    Route::get('/refunds',                  [AdminBooking::class,'refunds'])->name('refunds.index');
    Route::post('/refunds/{refund}/approve',[AdminBooking::class,'approveRefund'])->name('refunds.approve');
    Route::post('/refunds/{refund}/reject', [AdminBooking::class,'rejectRefund'])->name('refunds.reject');

    // Fleet & routes
    Route::get('/buses',           [FleetController::class,'buses'])->name('buses.index');
    Route::post('/buses',          [FleetController::class,'storeBus'])->name('buses.store');
    Route::get('/routes',          [FleetController::class,'routes'])->name('routes.index');
    Route::post('/routes',         [FleetController::class,'storeRoute'])->name('routes.store');
    Route::get('/schedules',       [FleetController::class,'schedules'])->name('schedules.index');
    Route::post('/schedules',      [FleetController::class,'storeSchedule'])->name('schedules.store');
    Route::post('/fares',          [FleetController::class,'storeFare'])->name('fares.store');
    Route::get('/cities',          [FleetController::class,'cities'])->name('cities.index');
    Route::post('/cities',         [FleetController::class,'storeCity'])->name('cities.store');
    Route::get('/terminals',       [FleetController::class,'terminals'])->name('terminals.index');
    Route::post('/terminals',      [FleetController::class,'storeTerminal'])->name('terminals.store');

    // Users & operators
    Route::get('/users',    fn()=>view('admin.users.index',  ['users'=>\App\Models\User::with('roles')->latest()->paginate(20)]))->name('users.index');
    Route::get('/operators',fn()=>view('admin.operators.index',['operators'=>\App\Models\Operator::with('user')->latest()->paginate(20)]))->name('operators.index');

    // Support
    Route::get('/support',                fn()=>view('admin.support.index',['tickets'=>\App\Models\SupportTicket::with('user')->latest()->paginate(20)]))->name('support.index');
    Route::get('/support/{ticket}',       fn($t)=>view('admin.support.show',['ticket'=>\App\Models\SupportTicket::with(['user','replies.user'])->findOrFail($t)]))->name('support.show');
    Route::post('/support/{ticket}/reply',fn(Request $req, $t)=>(fn($ticket)=>(
        \App\Models\SupportReply::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'message'=>$req->message,'is_staff_reply'=>true]),
        $ticket->update(['status'=>'in_progress','first_response_at'=>$ticket->first_response_at??now()]),
        back()->with('success','Reply sent.')
    ))(\App\Models\SupportTicket::findOrFail($t)))->name('support.reply');

    // Settings
    Route::get('/settings/{group?}', [SettingsController::class,'index'])->name('settings.index');
    Route::post('/settings/{group}', [SettingsController::class,'update'])->name('settings.update');
    Route::get('/settings/pwa',      [SettingsController::class,'pwa'])->name('settings.pwa');
    Route::post('/settings/pwa',     [SettingsController::class,'updatePwa'])->name('settings.pwa.update');
    Route::get('/settings/ai',       [SettingsController::class,'aiSettings'])->name('settings.ai');
    Route::post('/settings/ai',      [SettingsController::class,'updateAiSettings'])->name('settings.ai.update');

    // Reports
    Route::get('/reports', fn()=>view('admin.reports.index',['stats'=>[]]))->name('reports.index');
});

// ── Webhooks (no CSRF) ────────────────────────────────────────────────────────
Route::post('/webhooks/paystack', [WebhookController::class,'paystack'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.paystack');

// ── API for PWA ───────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('api/v1')->name('api.')->group(function() {
    Route::get('/user',    fn() => auth()->user());
    Route::get('/cities',  fn() => \App\Models\City::active()->orderBy('name')->get());
    Route::get('/notifications', fn() => auth()->user()->unreadNotifications->take(10));
    Route::post('/notifications/read', fn() => auth()->user()->unreadNotifications->markAsRead());
});
