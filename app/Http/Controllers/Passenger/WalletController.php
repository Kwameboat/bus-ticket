<?php
namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\{WalletService};
use App\Services\Payment\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService, private PaystackService $paystackService) {}

    public function index()
    {
        $wallet       = Auth::user()->getOrCreateWallet();
        $transactions = WalletTransaction::where('user_id',Auth::id())->latest()->paginate(20);
        return view('passenger.wallet.index', compact('wallet','transactions'));
    }

    public function fund(Request $request)
    {
        $request->validate(['amount'=>'required|numeric|min:1|max:10000']);
        $ref     = 'WLT-FUND-'.strtoupper(Str::random(12));
        $booking = null; // wallet top-up has no booking

        // Create a dummy payment record for tracking
        \App\Models\Payment::create([
            'booking_id'  => 0, // will be overridden below
            'user_id'     => Auth::id(),
            'amount'      => $request->amount,
            'currency'    => 'GHS',
            'method'      => 'card',
            'gateway'     => 'paystack',
            'gateway_ref' => $ref,
            'status'      => 'pending',
            'meta'        => ['type'=>'wallet_fund'],
        ]);

        // For wallet funding we call Paystack directly
        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.paystack.secret_key'))
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email'        => Auth::user()->email,
                    'amount'       => (int)round($request->amount * 100),
                    'currency'     => 'GHS',
                    'reference'    => $ref,
                    'callback_url' => route('wallet.fund.callback', ['ref'=>$ref]),
                    'metadata'     => ['type'=>'wallet_fund','user_id'=>Auth::id()],
                ]);

            $url = $response->json('data.authorization_url');
            if (!$url) return back()->with('error','Payment initialization failed.');
            return redirect($url);
        } catch (\Exception $e) {
            return back()->with('error','Could not connect to payment gateway.');
        }
    }

    public function fundCallback(Request $request)
    {
        $ref      = $request->get('reference') ?? $request->get('ref');
        $payment  = \App\Models\Payment::where('gateway_ref',$ref)->first();
        if (!$payment) return redirect()->route('passenger.wallet')->with('error','Transaction not found.');

        $verification = $this->paystackService->verify($ref);
        if ($verification['success']) {
            $payment->update(['status'=>'success','verified_at'=>now(),'verification_response'=>$verification['data']]);
            $this->walletService->fund(Auth::user(), $verification['amount'], $ref, 'Wallet top-up via Paystack');
            return redirect()->route('passenger.wallet')->with('success','₵'.number_format($verification['amount'],2).' added to your wallet!');
        }
        $payment->update(['status'=>'failed']);
        return redirect()->route('passenger.wallet')->with('error','Payment failed. Please try again.');
    }
}
