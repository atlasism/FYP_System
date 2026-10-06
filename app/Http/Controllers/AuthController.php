<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            ->where(function ($query) use ($credentials) {
                $query->where(function ($students) use ($credentials) {
                    $students->where('role', 'Student')->where('ic_number', $credentials['identifier']);
                })->orWhere(function ($staff) use ($credentials) {
                    $staff->whereIn('role', ['Supervisor', 'Admin', 'Panel'])
                        ->where('email', $credentials['identifier']);
                });
            })->first();

        $usesPrivatePassword = $account && in_array($account->role, ['Student', 'Admin'], true)
            && $account->password_changed_at !== null;
        $valid = $account && ($usesPrivatePassword
            ? Hash::check($credentials['password'], (string) $account->password)
            : (filled($account->ic_number) && hash_equals((string) $account->ic_number, $credentials['password'])));

        if (! $valid) {
            return back()->withErrors(['identifier' => 'The IC number or staff email and password do not match.'])->onlyInput('identifier');
        }

        // Existing accounts may still have legacy passwords. Replace them after
        // their first successful IC login so the database stores only a hash.
        if (! $usesPrivatePassword && ! Hash::check($credentials['password'], (string) $account->password)) {
            $account->password = Hash::make($account->ic_number);
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
        ]);

        $account = User::query()->create([
            ...$data,
            'username' => $data['ic_number'],
            'password' => Hash::make($data['ic_number']),
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
