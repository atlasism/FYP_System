<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPInE') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('home') }}">
        <img src="{{ asset('brand/logosistem.png') }}" alt="">
        <span><strong>SPInE</strong><small>Final Year Project System</small></span>
    </a>
    <nav>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="nav-user">{{ auth()->user()->full_name }} · {{ auth()->user()->role }}</span>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="link-button">Sign out</button></form>
        @else
            <a href="{{ route('login') }}">Sign in</a><a class="nav-cta" href="{{ route('register') }}">Student registration</a>
        @endauth
    </nav>
</header>
<main class="page-shell">
    @if (session('status')) <div class="notice success">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="notice error"><strong>Please check the form.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    @yield('content')
</main>
<footer>SPInE · Politeknik Besut · Project coordination and assessment</footer>
</body>
</html>
