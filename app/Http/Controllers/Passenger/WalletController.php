<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        $wallet = Wallet::firstOrCreate(['user_id' => auth()->id()], ['balance' => 0]);
        return view('passenger.wallet.index', compact('wallet'));
    }

    public function fund(Request $request)
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:1']]);
        return redirect()->route('passenger.wallet.fund.callback')->with('status', 'Redirect to payment gateway...');
    }

    public function fundCallback(Request $request)
    {
        return redirect()->route('passenger.wallet')->with('success', 'Wallet funded successfully.');
    }
}
