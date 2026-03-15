<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\{Payment, Booking};
use App\Services\BookingService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private WalletService  $walletService
    ) {}

    public function paystack(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Paystack-Signature');

        // Verify signature
        $secretKey = config('services.paystack.secret_key');
        $computed  = hash_hmac('sha512', $payload, $secretKey);

        if (!hash_equals($computed, $signature ?? '')) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['status'=>'invalid signature'], 400);
        }

        $data  = json_decode($payload, true);
        $event = $data['event'] ?? '';

        Log::info("Paystack webhook: {$event}", ['ref'=>$data['data']['reference'] ?? '']);

        match($event) {
            'charge.success'     => $this->handleChargeSuccess($data['data']),
            'transfer.success'   => $this->handleTransferSuccess($data['data']),
            'refund.processed'   => $this->handleRefundProcessed($data['data']),
            default              => null,
        };

        return response()->json(['status'=>'ok']);
    }

    private function handleChargeSuccess(array $data): void
    {
        $ref     = $data['reference'] ?? '';
        $payment = Payment::where('gateway_ref', $ref)->with('booking')->first();

        if (!$payment || $payment->status === 'success') return;

        $payment->update([
            'status'                => 'success',
            'webhook_payload'       => $data,
            'verified_at'           => now(),
        ]);

        // Wallet top-up
        if (($data['metadata']['type'] ?? '') === 'wallet_fund') {
            $user = \App\Models\User::find($data['metadata']['user_id']);
            if ($user) {
                $this->walletService->fund($user, $data['amount']/100, $ref, 'Wallet top-up via Paystack');
            }
            return;
        }

        // Booking payment
        if ($payment->booking && $payment->booking->booking_status === 'pending') {
            $this->bookingService->confirm($payment->booking);
        }
    }

    private function handleTransferSuccess(array $data): void
    {
        $ref    = $data['reference'] ?? '';
        $refund = \App\Models\Refund::where('gateway_refund_ref', $ref)->first();
        if ($refund) {
            $refund->update(['status'=>'processed','processed_at'=>now()]);
        }
    }

    private function handleRefundProcessed(array $data): void
    {
        Log::info('Paystack refund processed', $data);
    }
}
