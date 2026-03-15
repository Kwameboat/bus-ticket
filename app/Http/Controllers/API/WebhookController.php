<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function paystack(Request $request)
    {
        $secretKey = config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();

        if (!hash_equals(hash_hmac('sha512', $payload, $secretKey), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json('event');
        $data  = $request->json('data');

        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;
            if ($reference) {
                Booking::where('payment_reference', $reference)->update(['status' => 'confirmed', 'paid_at' => now()]);
            }
        }

        Log::info('Paystack webhook', ['event' => $event]);

        return response()->json(['ok' => true]);
    }
}
