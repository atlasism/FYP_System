<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'ic_number' => ['required', 'string', 'max:30', Rule::unique('users', 'ic_number')->ignore($user->id), Rule::unique('users', 'username')->ignore($user->id)],
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'different:ic_number'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'password.different' => 'Password must be different from your IC number.',
        ]);

        $icChanged = (string) $user->ic_number !== (string) $data['ic_number'];
        $usesDefaultPassword = $user->password_changed_at === null;
        $user->ic_number = $data['ic_number'];
        $user->username = $data['ic_number'];
        $user->full_name = $data['full_name'];
        $user->email = $data['email'];

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = now();
        } elseif ($icChanged && $usesDefaultPassword) {
            $user->password = Hash::make($data['ic_number']);
        }

        if ($request->hasFile('profile_picture')) {
            $directory = public_path('uploads/profile');
            File::ensureDirectoryExists($directory);
            $file = $request->file('profile_picture');
            $filename = 'admin_'.$user->id.'_'.bin2hex(random_bytes(12)).'.'.$file->extension();
            $file->move($directory, $filename);
            $user->profile_picture = $filename;
        }

        $user->save();

        return redirect()->route(match ($user->role) {
            'Admin' => 'admin.profile.edit',
            'Supervisor' => 'supervisor.profile.edit',
            'Panel' => 'panel.profile.edit',
            default => 'admin.profile.edit',
        })->with('status', 'Your profile has been updated.');
    }
}
