<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPInE') | Politeknik Besut</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('app.css') }}?v=2">
    <script defer src="{{ asset('portal.js') }}?v=1"></script>
</head>
@php($isLogin = request()->routeIs('login'))
@php($role = auth()->user()?->role)
<body class="{{ $isLogin ? 'login-page' : ($role ? 'portal-page' : 'site-page') }}">
@if($isLogin)
    <main class="login-stage">
        @if($errors->any())<div class="notice error login-notice"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if(session('status'))<div class="notice success login-notice">{{ session('status') }}</div>@endif
        @yield('content')
    </main>
@elseif($role)
    <div class="portal-shell">
        <aside class="portal-sidebar" id="portal-sidebar">
            <a class="sidebar-brand" href="{{ route('dashboard') }}"><span class="sidebar-logo"><img src="{{ asset('brand/logosistem.png') }}" alt="SPInE logo"></span><span><strong>SPInE</strong><small>Politeknik Besut</small></span></a>
            <div class="department-label"><i class="fa-solid fa-building-columns"></i> JTMK | DFT50114</div>
            <nav class="sidebar-nav" aria-label="{{ $role }} navigation">
                <a @class(['active' => request()->routeIs('*.dashboard')]) href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                @if($role === 'Student')
                    @if(isset($project) && $project)
                        <a href="{{ route('student.dashboard') }}#project"><i class="fa-solid fa-folder-open"></i> My Project</a>
                    @else
                        <a @class(['active' => request()->routeIs('student.projects.create')]) href="{{ route('student.projects.create') }}"><i class="fa-solid fa-folder-plus"></i> Register Project</a>
                    @endif
                    <a href="{{ route('student.dashboard') }}#submissions"><i class="fa-solid fa-file-arrow-up"></i> Upload Documents</a>
                    <a href="{{ route('student.dashboard') }}#assessment"><i class="fa-solid fa-chart-simple"></i> Marks & Deadlines</a>
                @elseif($role === 'Supervisor')
                    <a href="{{ route('supervisor.dashboard') }}#projects"><i class="fa-solid fa-diagram-project"></i> Supervised Projects</a>
                    <a href="{{ route('supervisor.dashboard') }}#documents"><i class="fa-solid fa-file-circle-check"></i> Review Documents</a>
                @elseif($role === 'Admin')
                    <a @class(['active' => request()->routeIs('admin.users.*')]) href="{{ route('admin.users.index') }}"><i class="fa-solid fa-users"></i> Manage Users</a>
                    <a @class(['active' => request()->routeIs('admin.projects.*')]) href="{{ route('admin.projects.index') }}"><i class="fa-solid fa-list-check"></i> Manage Projects</a>
                    <a @class(['active' => request()->routeIs('admin.deadlines.*')]) href="{{ route('admin.deadlines.index') }}"><i class="fa-solid fa-calendar-days"></i> Deadlines</a>
                    <a @class(['active' => request()->routeIs('admin.reports')]) href="{{ route('admin.reports') }}"><i class="fa-solid fa-chart-column"></i> Reports</a>
                    <a @class(['active' => request()->routeIs('admin.settings')]) href="{{ route('admin.settings') }}"><i class="fa-solid fa-gear"></i> Settings</a>
                @endif
                @if(in_array($role, ['Student', 'Admin'], true))
                    <a @class(['active' => request()->routeIs('password.*')]) href="{{ route('password.edit') }}"><i class="fa-solid fa-key"></i> Change Password</a>
                @endif
            </nav>
            <form class="sidebar-logout" method="post" action="{{ route('logout') }}">@csrf<button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
        </aside>
        <div class="portal-main">
            <header class="portal-topbar"><button class="sidebar-toggle" type="button" aria-controls="portal-sidebar" aria-expanded="false" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button><span class="topbar-title">{{ $role }} Portal</span><div class="topbar-right"><a href="{{ route('home') }}"><i class="fa-solid fa-house"></i> Home</a><span class="signed-in"><small>Signed in as</small><strong>{{ auth()->user()->full_name }}</strong></span><span class="avatar"><i class="fa-solid fa-user"></i></span></div></header>
            <main class="page-shell">
                @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
                @if($errors->any())<div class="notice error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
        </div>
    </div>
    <footer class="site-footer">&copy; {{ date('Y') }} <strong>SPInE FYP</strong> | Politeknik Besut, Terengganu. Hak Cipta Terpelihara.</footer>
@else
    <header class="site-header"><nav class="nav-wrap" aria-label="Main navigation"><a class="brand" href="{{ route('home') }}"><span class="system-logo"><img src="{{ asset('brand/logosistem.png') }}" alt="SPInE logo"></span><img class="college-logo" src="{{ asset('brand/politeknik-besut.png') }}" alt="Politeknik Besut logo"><strong>SPInE Politeknik Besut</strong></a><div class="nav-actions"><a href="{{ route('home') }}"><i class="fa-solid fa-house"></i> Home</a><a href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a></div></nav></header>
    <main class="page-shell public-shell">
        @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <footer class="site-footer">&copy; {{ date('Y') }} <strong>SPInE FYP</strong> | Politeknik Besut, Terengganu. Hak Cipta Terpelihara.</footer>
@endif
</body></html>
