<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPInE') | Politeknik Besut</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('app.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('student-legacy.css') }}?v=7">
    <link rel="stylesheet" href="{{ asset('portal-motion.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('supervisor-portal.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('admin-dashboard.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('user-preferences.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('portal-navbar.css') }}?v=6">
    <script defer src="{{ asset('portal.js') }}?v=3"></script>
    <script defer src="{{ asset('user-preferences.js') }}?v=2"></script>
</head>
@php($isLogin = request()->routeIs('login'))
@php($role = auth()->user()?->role)
<body class="{{ $isLogin ? 'login-page' : ($role ? 'portal-page user-portal'.($role === 'Student' ? ' student-portal' : '').($role === 'Supervisor' ? ' supervisor-portal' : '').($role === 'Admin' ? ' admin-portal' : '').($role === 'Panel' ? ' panel-portal' : '') : 'site-page') }}">
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
            @if($role === 'Admin')<div class="department-label"><i class="fa-solid fa-building-columns"></i> JTMK | DFT50114</div>@endif
            <nav class="sidebar-nav" aria-label="{{ $role }} navigation">
                @if($role === 'Student')
                    <a @class(['active' => request()->routeIs('student.dashboard')]) href="{{ route('student.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
                    @if(isset($project) && $project)
                        <a @class(['active' => request()->routeIs('student.projects.show')]) href="{{ route('student.projects.show') }}"><i class="fa-solid fa-folder-open"></i> My Project</a>
                    @else
                        <a @class(['active' => request()->routeIs('student.projects.create')]) href="{{ route('student.projects.create') }}"><i class="fa-solid fa-folder-plus"></i> Register Project</a>
                    @endif
                    <a @class(['active' => request()->routeIs('student.documents.index')]) href="{{ route('student.documents.index') }}"><i class="fa-solid fa-file-arrow-up"></i> Upload Documents</a>
                    <a @class(['active' => request()->routeIs('student.milestones.index')]) href="{{ route('student.milestones.index') }}"><i class="fa-solid fa-chart-simple"></i> Evaluation Marks</a>
                    <a @class(['active' => request()->routeIs('student.deadlines.index')]) href="{{ route('student.deadlines.index') }}"><i class="fa-solid fa-calendar-days"></i> Deadline Reminders</a>
                    <a @class(['active' => request()->routeIs('student.groups.index')]) href="{{ route('student.groups.index') }}"><i class="fa-solid fa-users"></i> Group List</a>
                    <a @class(['active' => request()->routeIs('student.archive.index')]) href="{{ route('student.archive.index') }}"><i class="fa-solid fa-box-archive"></i> Past Projects Archive</a>
                @elseif($role === 'Supervisor')
                    <a @class(['active' => request()->routeIs('supervisor.dashboard')]) href="{{ route('supervisor.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
                    <a @class(['active' => request()->routeIs('supervisor.projects.*')]) href="{{ route('supervisor.projects.index') }}"><i class="fa-solid fa-diagram-project"></i> Supervised Projects</a>
                    <a @class(['active' => request()->routeIs('supervisor.students.*')]) href="{{ route('supervisor.students.index') }}"><i class="fa-solid fa-user-check"></i> Student Verification</a>
                    <a @class(['active' => request()->routeIs('supervisor.archive.*')]) href="{{ route('supervisor.archive.index') }}"><i class="fa-solid fa-box-archive"></i> Past Projects</a>
                    <a @class(['active' => request()->routeIs('supervisor.documents.*')]) href="{{ route('supervisor.documents.index') }}"><i class="fa-solid fa-file-lines"></i> Review Documents</a>
                    <a @class(['active' => request()->routeIs('supervisor.deadlines.*')]) href="{{ route('supervisor.deadlines.index') }}"><i class="fa-solid fa-calendar-days"></i> Deadline Reminders</a>
                @elseif($role === 'Admin')
                    <a @class(['active' => request()->routeIs('*.dashboard')]) href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                    <a @class(['active' => request()->routeIs('admin.users.*')]) href="{{ route('admin.users.index') }}"><i class="fa-solid fa-users"></i> Manage Users</a>
                    <a @class(['active' => request()->routeIs('admin.projects.*')]) href="{{ route('admin.projects.index') }}"><i class="fa-solid fa-list-check"></i> Manage Projects</a>
                    <a @class(['active' => request()->routeIs('admin.panel-qr.*')]) href="{{ route('admin.panel-qr.index') }}"><i class="fa-solid fa-qrcode"></i> External Panel QR</a>
                    <a @class(['active' => request()->routeIs('admin.reports')]) href="{{ route('admin.reports') }}"><i class="fa-solid fa-chart-column"></i> Reports</a>
                    <a @class(['active' => request()->routeIs('admin.settings')]) href="{{ route('admin.settings') }}"><i class="fa-solid fa-gear"></i> Settings</a>
                @else
                    <a @class(['active' => request()->routeIs('*.dashboard')]) href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                @endif
            </nav>
            <form class="sidebar-logout" method="post" action="{{ route('logout') }}">@csrf<button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
        </aside>
        <div class="portal-main">
            <header class="portal-topbar">
                <a class="portal-topbar-brand" href="{{ route('dashboard') }}"><span class="sidebar-logo"><img src="{{ asset('brand/logosistem.png') }}" alt=""></span><span><strong>SPInE</strong><small>POLITEKNIK BESUT</small></span></a>
                <button class="sidebar-toggle" type="button" aria-controls="portal-sidebar" aria-expanded="false" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
                <div class="topbar-right">
                    <div class="appearance-controls portal-appearance-controls">
                        <div class="language-switch" data-language-switch aria-label="Language"><button type="button" data-language-option="en">EN</button><button type="button" data-language-option="ms">BM</button></div>
                        <button type="button" class="portal-theme-button" data-theme-toggle aria-label="Toggle dark mode" title="Light/Dark"><i data-theme-icon class="fa-solid fa-sun"></i></button>
                    </div>
                    <a href="{{ route('home') }}"><i class="fa-solid fa-house"></i> <span data-i18n="Home">Home</span></a>
                    <span class="signed-in"><small data-i18n="{{ $role === 'Student' ? 'Welcome' : 'Signed in as' }}">{{ $role === 'Student' ? 'Welcome' : 'Signed in as' }}</small>
                        @if($role === 'Student')<a class="signed-in-profile" href="{{ route('student.profile.edit') }}" aria-label="Edit profile for {{ auth()->user()->full_name }}"><strong>{{ auth()->user()->full_name }}</strong></a>
                        @elseif(in_array($role, ['Admin', 'Supervisor', 'Panel'], true))<a class="signed-in-profile" href="{{ route(strtolower($role).'.profile.edit') }}" aria-label="Edit profile for {{ auth()->user()->full_name }}"><strong>{{ auth()->user()->full_name }}</strong></a>@endif
                    </span>
                    @if($role === 'Student')<a class="avatar avatar-profile" href="{{ route('student.profile.edit') }}" aria-label="Edit profile"><i class="fa-solid fa-user"></i></a>@else<span class="avatar"><i class="fa-solid fa-user"></i></span>@endif
                </div>
            </header>
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
