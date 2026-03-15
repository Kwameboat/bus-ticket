<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User, City, Terminal, Route, RouteBoardingPoint, Operator, Bus, SeatPlan,
                Driver, Conductor, Schedule, Fare, Trip, TripSeat, Booking, BookingSeat,
                Payment, Wallet, WalletTransaction, QrTicket, Setting, Faq, Page, AiSettings};
use Carbon\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            GeographySeeder::class,
            FleetSeeder::class,
            ScheduleSeeder::class,
            BookingSeeder::class,
            SettingsSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
