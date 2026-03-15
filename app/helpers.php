<?php

if (!function_exists('setting')) {
    /**
     * Get a system setting value from the database.
     * Falls back to .env / default if not found.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            return \App\Models\Setting::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('format_ghs')) {
    function format_ghs(float $amount): string
    {
        return '₵' . number_format($amount, 2);
    }
}

if (!function_exists('booking_status_color')) {
    function booking_status_color(string $status): string
    {
        return match($status) {
            'confirmed'   => 'green',
            'pending'     => 'yellow',
            'cancelled'   => 'red',
            'completed'   => 'blue',
            'rescheduled' => 'purple',
            default       => 'gray',
        };
    }
}
