@extends('layouts.app')
@section('title', 'Change Password')
@section('content')
<div class="page-heading"><div><span class="eyebrow">ACCOUNT SECURITY</span><h1>Change Password</h1><p>Use your IC number if you have not changed your password before.</p></div></div>
<section class="panel-card" style="max-width:620px">
    <form method="post" action="{{ route('password.update') }}">
        @csrf @method('PUT')
        <label for="current_password">Current password<input id="current_password" name="current_password" type="password" required autocomplete="current-password"></label>
        <label for="password">New password<input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"></label>
        <label for="password_confirmation">Confirm new password<input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></label>
        <p>Use at least 12 characters and choose a password different from your IC number.</p>
        <button class="button primary" type="submit">Save new password</button>
    </form>
</section>
@endsection
