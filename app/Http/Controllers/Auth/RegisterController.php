<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{User, Wallet};
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:200',
            'email'    => 'required|email|unique:users|max:200',
            'phone'    => 'required|string|max:20|unique:users',
            'password' => ['required','confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('passenger');
        Wallet::create(['user_id' => $user->id]);
        event(new Registered($user));

        try { $user->notify(new WelcomeNotification()); } catch (\Exception $e) {}

        Auth::login($user);
        return redirect()->route('passenger.dashboard')->with('success', 'Welcome to GhanaBus Connect!');
    }
}
