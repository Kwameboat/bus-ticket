<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\{BookingService, QrService, WalletService, WaitlistService};
use App\Services\AI\AiService;
use App\Services\Payment\PaystackService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services as singletons
        $this->app->singleton(QrService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(WaitlistService::class);
        $this->app->singleton(AiService::class);
        $this->app->singleton(PaystackService::class);
        $this->app->singleton(BookingService::class, function($app) {
            return new BookingService($app->make(QrService::class), $app->make(WalletService::class));
        });
    }

    public function boot(): void
    {
        // Global setting() helper registered in helpers.php
    }
}
