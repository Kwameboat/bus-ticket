<?php
namespace App\Services\Payment;

use App\Models\{Booking, Payment};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    private string $secretKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Initialize a Paystack transaction
     */
    public function initializeTransaction(Booking $booking, string $method = 'card'): array
    {
        $ref = 'GBC-PAY-'.strtoupper(Str::random(12));

        $channels = match($method) {
            'momo'  => ['mobile_money'],
            'ussd'  => ['ussd'],
            default => ['card','mobile_money','ussd','bank_transfer'],
        };

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'        => $booking->user->email,
                'amount'       => (int)round($booking->grand_total * 100), // Paystack uses pesewas
                'currency'     => 'GHS',
                'reference'    => $ref,
                'callback_url' => route('payment.callback', ['ref' => $ref]),
                'metadata'     => [
                    'booking_ref' => $booking->booking_ref,
                    'booking_id'  => $booking->id,
                    'user_id'     => $booking->user_id,
                    'custom_fields' => [
                        ['display_name' => 'Booking Reference', 'variable_name' => 'booking_ref', 'value' => $booking->booking_ref],
                        ['display_name' => 'Route', 'variable_name' => 'route', 'value' => $booking->trip->route->name ?? ''],
                    ],
                ],
                'channels' => $channels,
            ]);

        if (!$response->successful() || !$response->json('status')) {
            throw new \Exception('Paystack initialization failed: '.($response->json('message') ?? 'Unknown error'));
        }

        $data = $response->json('data');

        // Create pending payment record
        Payment::create([
            'booking_id'          => $booking->id,
            'user_id'             => $booking->user_id,
            'amount'              => $booking->grand_total,
            'currency'            => 'GHS',
            'method'              => $method,
            'gateway'             => 'paystack',
            'gateway_ref'         => $ref,
            'gateway_access_code' => $data['access_code'] ?? null,
            'status'              => 'pending',
        ]);

        return [
            'authorization_url' => $data['authorization_url'],
            'access_code'       => $data['access_code'],
            'reference'         => $ref,
        ];
    }

    /**
     * Verify a transaction by reference
     */
    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful()) {
            throw new \Exception('Paystack verification failed');
        }

        $data   = $response->json('data');
        $status = $response->json('status');

        return [
            'success'   => $status && ($data['status'] ?? '') === 'success',
            'data'      => $data,
            'reference' => $reference,
            'amount'    => ($data['amount'] ?? 0) / 100,
            'message'   => $data['gateway_response'] ?? '',
        ];
    }

    /**
     * Handle webhook verification
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($computed, $signature);
    }
}
