<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Setting, Faq, Page, AiSettings};

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['general', 'site_name',          'GhanaBus Connect',     'string',  'Site Name'],
            ['general', 'site_tagline',        'Book Bus Tickets Across Ghana', 'string', 'Tagline'],
            ['general', 'currency',            'GHS',                  'string',  'Currency Code'],
            ['general', 'currency_symbol',     '₵',                    'string',  'Currency Symbol'],
            ['general', 'support_email',       'support@ghanabusconnect.com', 'string', 'Support Email'],
            ['general', 'support_phone',       '+233 20 000 0000',     'string',  'Support Phone'],

            // Booking
            ['booking', 'cancellation_window_hours',  '4',   'int',   'Cancellation Window (hours before departure)'],
            ['booking', 'cancellation_refund_percent','100',  'int',   'Refund % on cancellation within window'],
            ['booking', 'seat_lock_minutes',          '12',   'int',   'Seat lock duration in minutes'],
            ['booking', 'booking_expiry_minutes',     '30',   'int',   'Unpaid booking expires after (minutes)'],
            ['booking', 'max_seats_per_booking',      '6',    'int',   'Max seats per booking'],
            ['booking', 'service_charge_percent',     '2',    'float', 'Service charge %'],

            // Waitlist
            ['booking', 'waitlist_enabled',           '1',    'bool',  'Enable waitlist globally'],
            ['booking', 'waitlist_claim_minutes',     '30',   'int',   'Minutes for passenger to claim waitlisted seat'],

            // PWA
            ['pwa', 'pwa_app_name',      'GhanaBus Connect',  'string', 'PWA App Name'],
            ['pwa', 'pwa_short_name',    'GhanaBus',          'string', 'PWA Short Name'],
            ['pwa', 'pwa_theme_color',   '#1A56DB',           'string', 'PWA Theme Color'],
            ['pwa', 'pwa_bg_color',      '#FFFFFF',           'string', 'PWA Background Color'],
            ['pwa', 'pwa_description',   'Book intercity bus tickets across Ghana', 'string', 'PWA Description'],
            ['pwa', 'pwa_install_delay', '3',                 'int',    'Install prompt delay (seconds)'],
            ['pwa', 'pwa_re_show_days',  '7',                 'int',    'Re-show install prompt after (days)'],

            // Notifications
            ['notification', 'email_booking_confirmed', '1', 'bool', 'Email on booking confirmation'],
            ['notification', 'email_trip_reminder',     '1', 'bool', 'Email trip reminder (24h before)'],
            ['notification', 'email_waitlist_available','1', 'bool', 'Email waitlist seat available'],

            // Operator
            ['operator', 'default_commission_rate', '10', 'float', 'Default operator commission %'],
        ];

        foreach ($settings as [$group, $key, $value, $cast, $label]) {
            Setting::firstOrCreate(['key'=>$key], [
                'group'=>$group,'value'=>$value,'cast_type'=>$cast,'label'=>$label,
            ]);
        }

        // AI Settings
        AiSettings::firstOrCreate([], [
            'provider'                  => 'mock',
            'model'                     => 'gpt-4o-mini',
            'api_key_env'               => 'OPENAI_API_KEY',
            'max_tokens'                => 800,
            'temperature'               => 0.7,
            'rate_limit_per_hour'       => 20,
            'enabled'                   => true,
            'passenger_chat_enabled'    => true,
            'admin_insights_enabled'    => false,
            'seat_recommendations_enabled' => false,
            'passenger_system_prompt'   => "You are a helpful GhanaBus Connect assistant. Help passengers book intercity bus tickets in Ghana. Be friendly, concise, and helpful. Currency is GHS (Ghana Cedi ₵). Popular routes: Accra→Kumasi (₵80), Accra→Takoradi (₵65), Accra→Cape Coast (₵40). Always suggest passengers use the search to find real-time availability.",
            'admin_system_prompt'       => "You are an analytics assistant for GhanaBus Connect admin. Provide concise, actionable insights about bookings, revenue, and route performance.",
        ]);

        $this->command->info('Settings seeded.');
    }
}
