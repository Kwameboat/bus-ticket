<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, Storage};

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $request->validate([
            'name'   => 'required|string|max:200',
            'phone'  => 'required|string|max:20|unique:users,phone,'.$user->id,
            'gender' => 'nullable|in:male,female,other',
            'dob'    => 'nullable|date|before:today',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name','phone','gender','dob','address','city','emergency_name','emergency_phone','id_type','id_number');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars','public');
        }

        if ($request->filled('new_password')) {
            $request->validate(['current_password'=>'required','new_password'=>'min:8|confirmed']);
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password'=>'Current password is incorrect.']);
            }
            $data['password'] = Hash::make($request->new_password);
        }

        $user->update($data);
        return back()->with('success', 'Profile updated successfully.');
    }
}

class EmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('passenger.dashboard');
        }
        $request->fulfill();
        return redirect()->route('passenger.dashboard')->with('success', 'Email verified successfully!');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('passenger.dashboard');
        }
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Verification link sent.');
    }
}
