@extends('layouts.app')
@section('title', 'My Profile')
@section('content')
<div class="profile-page-wrap">
    <section class="panel-card student-profile-card">
        <h1><i class="fa-solid fa-id-card"></i> My Profile</h1>
        <form method="post" action="{{ route('student.profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="profile-picture-field">
                <label for="profile_picture">Profile Picture</label>
                @if($user->profile_picture && is_file(public_path('uploads/profile/'.$user->profile_picture)))
                    <img class="profile-picture-preview" src="{{ asset('uploads/profile/'.$user->profile_picture) }}" alt="Current profile picture">
                @else
                    <span class="profile-picture-placeholder"><i class="fa-solid fa-user"></i></span>
                @endif
                <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG or WEBP, maximum 2 MB.</small>
            </div>
            <label for="ic_number">IC Number<input id="ic_number" name="ic_number" value="{{ old('ic_number', $user->ic_number) }}" required maxlength="30" autocomplete="off"></label>
            <label for="matric_no">Matric Number<input id="matric_no" name="matric_no" value="{{ old('matric_no', $user->matric_no) }}" required maxlength="30" pattern="[A-Za-z0-9]+"></label>
            <label for="department">Department<input id="department" value="{{ $user->department }}" disabled></label>
            <label for="full_name">Full Name<input id="full_name" value="{{ $user->full_name }}" readonly></label>
            <label for="email">Email<input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="100" autocomplete="email"></label>
            <label for="password">Change Password <span>(at least 8 characters, different from your IC; leave blank to keep current)</span><input id="password" name="password" type="password" minlength="8" autocomplete="new-password">@error('password')<small class="profile-field-error">{{ $message }}</small>@enderror</label>
            <label for="password_confirmation">Confirm New Password<input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
            <button class="button primary full" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
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
