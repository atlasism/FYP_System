<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $currentIsValid = $user->password_changed_at
            ? Hash::check($data['current_password'], (string) $user->password)
            : hash_equals((string) $user->ic_number, $data['current_password']);

        if (! $currentIsValid) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }
        if (hash_equals((string) $user->ic_number, $data['password'])) {
            return back()->withErrors(['password' => 'Choose a password different from your IC number.']);
        }

        $user->password = Hash::make($data['password']);
        $user->password_changed_at = now();
        $user->save();
        $request->session()->regenerate();

        return redirect()->route('password.edit')->with('status', 'Your password has been changed.');
    }
}
