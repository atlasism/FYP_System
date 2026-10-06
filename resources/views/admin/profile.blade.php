@extends('layouts.app')
@section('title', $user->role.' Profile')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-profile.css') }}?v=1">
<div class="admin-profile-page">
    <div class="admin-profile-heading">
        <div><h1><i class="fa-solid fa-user-gear"></i> {{ $user->role }} Profile</h1><p>Update your account details and password.</p></div>
        <a class="admin-profile-dashboard" href="{{ route(strtolower($user->role).'.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <section class="admin-profile-card">
        <form method="post" action="{{ route(strtolower($user->role).'.profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="admin-profile-picture-field">
                <label for="profile_picture">Profile Picture</label>
                @if($user->profile_picture && is_file(public_path('uploads/profile/'.$user->profile_picture)))
                    <img class="admin-profile-picture" src="{{ asset('uploads/profile/'.$user->profile_picture) }}" alt="Current profile picture">
                @else
                    <span class="admin-profile-picture-placeholder"><i class="fa-solid fa-user"></i></span>
                @endif
                <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG or WEBP, maximum 2 MB.</small>
            </div>

            <label class="admin-profile-field" for="ic_number">IC Number<input id="ic_number" name="ic_number" type="text" value="{{ old('ic_number', $user->ic_number) }}" maxlength="30" required autocomplete="off"></label>
            <label class="admin-profile-field" for="full_name">Full Name<input id="full_name" name="full_name" type="text" value="{{ old('full_name', $user->full_name) }}" maxlength="100" required autocomplete="name"></label>
            <label class="admin-profile-field" for="email">Email<input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="100" required autocomplete="email"></label>
            <label class="admin-profile-field" for="department">Department<input id="department" type="text" value="{{ $user->department }}" disabled></label>
            <label class="admin-profile-field" for="password">Change Password<input id="password" name="password" type="password" minlength="8" maxlength="255" placeholder="Leave blank to keep current password" autocomplete="new-password"><small>Enter at least 8 characters, different from your IC number.</small>@error('password')<small class="profile-field-error">{{ $message }}</small>@enderror</label>
            <button class="admin-profile-save" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
        </form>
    </section>
</div>
<script>
(() => {
    const ic = document.getElementById('ic_number');
    const password = document.getElementById('password');
    if (!ic || !password) return;
    const checkDifference = () => password.setCustomValidity(password.value && password.value === ic.value ? 'Password must be different from your IC number.' : '');
    ic.addEventListener('input', checkDifference);
    password.addEventListener('input', checkDifference);
    password.form?.addEventListener('submit', checkDifference);
})();
</script>
@endsection
