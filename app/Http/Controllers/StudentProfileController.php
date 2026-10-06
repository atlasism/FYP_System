<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function edit(): View
    {
        return view('portal.student-profile', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $icChanged = (string) $user->ic_number !== (string) $request->input('ic_number');
        $usesDefaultPassword = $user->password_changed_at === null;
        $data = $request->validate([
            'ic_number' => ['required', 'string', 'max:30', Rule::unique('users', 'ic_number')->ignore($user->id), Rule::unique('users', 'username')->ignore($user->id)],
            'matric_no' => ['required', 'alpha_num', 'max:30', Rule::unique('users', 'matric_no')->ignore($user->id)],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed', 'different:ic_number'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'password.different' => 'Password must be different from your IC number.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $user->ic_number = $data['ic_number'];
        $user->username = $data['ic_number'];
        $user->matric_no = $data['matric_no'];
        $user->email = $data['email'];

        if (filled($data['password'] ?? null)) {
            if (hash_equals((string) $data['ic_number'], $data['password'])) {
                return back()->withErrors(['password' => 'Choose a password different from your IC number.'])->withInput($request->except('password', 'password_confirmation'));
            }
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = now();
        } elseif ($icChanged && $usesDefaultPassword) {
            $user->password = Hash::make($data['ic_number']);
        }

        if ($request->hasFile('profile_picture')) {
            $directory = public_path('uploads/profile');
            File::ensureDirectoryExists($directory);
            $file = $request->file('profile_picture');
            $filename = 'student_'.$user->id.'_'.bin2hex(random_bytes(12)).'.'.$file->extension();
            $file->move($directory, $filename);
            $user->profile_picture = $filename;
        }

        $user->save();

        return redirect()->route('student.profile.edit')->with('status', 'Your profile has been updated.');
    }
}
