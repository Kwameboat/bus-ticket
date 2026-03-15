<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Faq, Page};

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['Booking', 'How do I book a bus ticket?', 'Use the search form on the homepage. Enter your origin, destination, and travel date, then select a trip, choose your seat, fill in passenger details, and pay online.'],
            ['Booking', 'Can I book for multiple passengers?', 'Yes — you can book up to 6 seats in a single booking. Each seat requires the passenger\'s name.'],
            ['Booking', 'How long does it take to confirm my booking?', 'Confirmation is instant after successful payment. You\'ll receive a booking confirmation email with your QR-coded ticket immediately.'],
            ['Payment',  'What payment methods are accepted?', 'We accept Visa/Mastercard via Paystack, MTN Mobile Money, Telecel Cash, AirtelTigo Money, and the GhanaBus Wallet.'],
            ['Payment',  'Is my payment secure?', 'Yes. All payments are processed by Paystack, a PCI DSS-compliant payment gateway used by thousands of businesses in Ghana.'],
            ['Payment',  'What is the GhanaBus Wallet?', 'The GhanaBus Wallet is your in-app balance that you can top up and use to pay for bookings instantly. Refunds are also processed to your wallet.'],
            ['Cancellation', 'Can I cancel my booking?', 'Yes. You can cancel up to 4 hours before departure. Refunds are processed to your GhanaBus Wallet within minutes.'],
            ['Cancellation', 'What is the refund policy?', 'Full refunds are issued for cancellations made more than 4 hours before departure. Cancellations within 4 hours of departure are non-refundable.'],
            ['Cancellation', 'How do I get a refund?', 'Refunds are automatically credited to your GhanaBus Wallet when you cancel. Wallet refunds are instant. Bank transfers may take 3–5 business days.'],
            ['Boarding',   'How do I board the bus?', 'Show your QR-code ticket (in the app or printed) to the conductor at the terminal. Your QR code is scanned to confirm your booking.'],
            ['Boarding',   'What if I lose my ticket?', 'Your ticket is always available in the GhanaBus app under My Bookings. You can re-download or show the digital QR code on your phone.'],
            ['Boarding',   'How early should I arrive at the terminal?', 'Please arrive at least 30 minutes before your scheduled departure time.'],
            ['Waitlist',   'What is a waitlist?', 'If a trip is fully booked, you can join the waitlist. When a seat becomes available, you\'ll be notified and given 30 minutes to complete your booking.'],
        ];

        foreach ($faqs as [$category, $question, $answer]) {
            Faq::firstOrCreate(['question'=>$question], [
                'category'=>$category,'answer'=>$answer,'sort_order'=>0,'is_active'=>true,
            ]);
        }

        $pages = [
            ['about',          'About Us',         '<h2>About GhanaBus Connect</h2><p>GhanaBus Connect is Ghana\'s leading intercity bus ticket booking platform. We make it easy to search, book, and pay for bus travel across Ghana — all from your phone.</p><h3>Our Mission</h3><p>To make intercity travel in Ghana more convenient, transparent, and reliable for everyone.</p>'],
            ['terms',          'Terms of Use',     '<h2>Terms of Use</h2><p>By using GhanaBus Connect, you agree to these terms. Please read them carefully.</p><h3>1. Booking Policy</h3><p>All bookings are subject to availability. Prices are in Ghana Cedis (GHS) and are inclusive of applicable charges.</p><h3>2. Cancellation & Refunds</h3><p>Please refer to our refund policy for full details.</p>'],
            ['privacy',        'Privacy Policy',   '<h2>Privacy Policy</h2><p>GhanaBus Connect is committed to protecting your privacy. We collect only the information necessary to process your bookings.</p><h3>Data We Collect</h3><p>Name, email, phone number, payment information (processed securely by Paystack), and booking history.</p>'],
            ['refund-policy',  'Refund Policy',    '<h2>Refund Policy</h2><p>We want you to have a great experience with GhanaBus Connect.</p><h3>Standard Cancellations</h3><p>Cancel at least 4 hours before departure for a full refund to your GhanaBus Wallet.</p><h3>Late Cancellations</h3><p>Cancellations within 4 hours of departure are non-refundable.</p>'],
            ['faq',            'FAQs',             '<p>Find answers to common questions below, or contact our support team if you need further help.</p>'],
        ];

        foreach ($pages as [$slug, $title, $body]) {
            Page::firstOrCreate(['slug'=>$slug], [
                'title'=>$title,'body'=>$body,'is_active'=>true,'in_footer'=>true,
            ]);
        }

        $this->command->info('FAQs and pages seeded.');
    }
}
