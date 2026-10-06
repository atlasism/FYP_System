@extends('layouts.app')
@section('title', 'Student Registration')
@section('content')
<section class="register-card panel-card">
    <div class="register-heading"><i class="fa-solid fa-user-plus"></i><h1>New Account Registration</h1><small>SPInE Portal Politeknik Besut</small></div>
    <form method="post" action="{{ route('register') }}">@csrf
        <div class="form-row"><label class="full-field">Full Name <span class="required">*</span><input name="full_name" value="{{ old('full_name') }}" placeholder="E.g: AMINAH JUNAIDI" required maxlength="100"></label></div>
        <div class="form-row"><label>IC <span class="required">*</span><input name="ic_number" value="{{ old('ic_number') }}" placeholder="E.g: 340101011234" required maxlength="30"></label><label>Matrix No <span class="required">*</span><input name="matric_no" value="{{ old('matric_no') }}" placeholder="E.g: 34DIT2xFxxx" required maxlength="30"></label></div>
        <div class="form-row"><label class="full-field">Email <span class="required">*</span><input name="email" type="email" value="{{ old('email') }}" placeholder="name@gmail.com" required maxlength="100"></label></div>
        <p>After registration, sign in with your IC number as both the ID and initial password. You can change your password inside your account.</p>
        <div class="form-row"><label>Role<input value="Student" readonly></label><label>Department<input value="JTMK - Department of Information and Communication Technology" readonly></label></div>
        <button class="button primary full" type="submit"><i class="fa-solid fa-user-plus"></i> Register New Account</button>
    </form>
    <div class="register-foot"><small>Already have an account?</small><a href="{{ route('login') }}">Login</a></div>
</section>
@endsection
