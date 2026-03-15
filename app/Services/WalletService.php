<?php
namespace App\Services;

use App\Models\{Wallet, WalletTransaction, Booking, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function fund(User $user, float $amount, string $reference, string $description = 'Wallet top-up'): WalletTransaction
    {
        return DB::transaction(function() use ($user, $amount, $reference, $description) {
            $wallet = $user->getOrCreateWallet();
            $before = $wallet->balance;
            $wallet->increment('balance', $amount);
            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $user->id,
                'type'           => 'credit',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $before + $amount,
                'reference'      => $reference,
                'description'    => $description,
                'source'         => 'fund',
                'status'         => 'completed',
            ]);
        });
    }

    public function debit(User $user, float $amount, string $description, ?Booking $booking = null): WalletTransaction
    {
        return DB::transaction(function() use ($user, $amount, $description, $booking) {
            $wallet = $user->getOrCreateWallet();
            if (!$wallet->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient wallet balance');
            }
            $before = $wallet->balance;
            $wallet->decrement('balance', $amount);
            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $user->id,
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $before - $amount,
                'reference'      => 'WLT-'.strtoupper(Str::random(10)),
                'description'    => $description,
                'source'         => 'booking',
                'booking_id'     => $booking?->id,
                'status'         => 'completed',
            ]);
        });
    }

    public function refundToWallet(User $user, float $amount, Booking $booking, string $reason = 'Booking cancellation refund'): WalletTransaction
    {
        return $this->fund($user, $amount, 'REF-'.strtoupper(Str::random(10)), $reason);
    }

    public function getBalance(User $user): float
    {
        return $user->getOrCreateWallet()->balance;
    }
}
