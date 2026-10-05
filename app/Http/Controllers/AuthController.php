<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $account = User::query()
            ->where('email', $credentials['identifier'])
            ->orWhere('ic_number', $credentials['identifier'])
            ->first();

        $storedPassword = (string) ($account?->password ?? '');
        $isRecognizedHash = password_get_info($storedPassword)['algoName'] !== 'unknown';
        $valid = $account && ($isRecognizedHash
            ? Hash::check($credentials['password'], $storedPassword)
            : hash_equals($storedPassword, $credentials['password']));

        if (! $valid) {
            return back()->withErrors(['identifier' => 'The email or IC number and password do not match.'])->onlyInput('identifier');
        }

        if (! $isRecognizedHash) {
            $account->password = Hash::make($credentials['password']);
            $account->save();
        }

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'ic_number' => ['required', 'string', 'max:30', 'unique:users,ic_number'],
            'matric_no' => ['required', 'string', 'max:30', 'unique:users,matric_no'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $account = User::query()->create([
            ...$data,
            'username' => $data['ic_number'],
            'password' => Hash::make($data['password']),
            'role' => 'Student',
            'department' => 'JTMK',
            'program_name' => 'JTMK - Information Technology',
            'course_code' => 'DFT50114',
        ]);

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your student account has been created.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
