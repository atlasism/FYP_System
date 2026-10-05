@extends('layouts.app')
@section('title', 'Login')
@section('content')
<section class="login-glass" aria-labelledby="login-title">
    <div class="login-brand"><img src="{{ asset('brand/logosistem.png') }}" alt="SPInE Politeknik Besut"><h1 id="login-title">FYP Inventory<br>System</h1><small>Repository &amp; Management Platform</small></div>
    <form method="post" action="{{ route('login') }}">@csrf
        <label class="login-label" for="identifier">Matric Number (Student) or Email (Staff/Panel)</label>
        <div class="input-group"><span><i class="fa-solid fa-id-card"></i></span><input id="identifier" name="identifier" value="{{ old('identifier') }}" placeholder="Enter matric number or email" required autocomplete="username" autofocus></div>
        <label class="login-label" for="password">Password (IC Number)</label>
        <div class="input-group"><span><i class="fa-solid fa-lock"></i></span><input id="password" name="password" type="password" placeholder="Enter your IC number" required autocomplete="current-password"></div>
        <button class="login-submit" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
    </form>
    <div class="login-foot"><small>&copy; {{ date('Y') }} Politeknik Besut</small><a href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Home</a></div>
</section>
@endsection
