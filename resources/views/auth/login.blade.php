@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<section class="auth-wrap"><div class="auth-aside"><span class="eyebrow">WELCOME BACK</span><h1>Pick up where<br><em>your project left off.</em></h1><p>Sign in using the email address or IC number connected to your account.</p><a href="{{ route('home') }}">← Back to SPInE</a></div>
<form class="form-card" method="post" action="{{ route('login') }}">@csrf<span class="eyebrow">ACCOUNT ACCESS</span><h2>Sign in</h2><label>Email address or IC number<input name="identifier" value="{{ old('identifier') }}" required autocomplete="username" autofocus></label><label>Password<input name="password" type="password" required autocomplete="current-password"></label><button class="button primary full" type="submit">Continue to dashboard</button><p class="form-foot">New student? <a href="{{ route('register') }}">Create an account</a></p></form></section>
@endsection
