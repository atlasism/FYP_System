<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPInE Student Project System | Politeknik Besut</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('home.css') }}?v=1">
    <script defer src="{{ asset('home.js') }}?v=1"></script>
</head>
<body>
    <header class="site-header"><nav class="nav-wrap" aria-label="Main navigation">
        <a class="brand" href="{{ route('home') }}"><span class="system-logo"><img src="{{ asset('brand/logosistem.png') }}" alt="SPInE logo"></span><img class="college-logo" src="{{ asset('brand/politeknik-besut.png') }}" alt="Politeknik Besut logo"><span>SPInE Politeknik Besut</span></a>
        <div class="nav-actions">
            <div class="language-switch" role="group" aria-label="Language"><button type="button" data-language="en" aria-pressed="true">EN</button><button type="button" data-language="ms" aria-pressed="false">BM</button></div>
            @auth
                <a class="nav-button login-button" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge"></i> <span data-i18n="Dashboard">Dashboard</span></a>
                <form action="{{ route('logout') }}" method="post">@csrf<button class="nav-button outline-button" type="submit"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="Logout">Logout</span></button></form>
            @else
                <a class="nav-button login-button" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="Login">Login</span></a>
                <details class="manual-menu"><summary class="nav-button outline-button"><i class="fa-solid fa-book-open"></i> <span data-i18n="User Manual">User Manual</span> <i class="fa-solid fa-caret-down"></i></summary><div class="manual-options">
                    <a href="{{ route('manuals.show', 'student') }}" target="_blank" rel="noopener"><i class="fa-solid fa-user-graduate"></i> <span data-i18n="Student">Student</span></a>
                    <a href="{{ route('manuals.show', 'supervisor') }}" target="_blank" rel="noopener"><i class="fa-solid fa-chalkboard-user"></i> <span data-i18n="Supervisor">Supervisor</span></a>
                    <a href="{{ route('manuals.show', 'panel') }}" target="_blank" rel="noopener"><i class="fa-solid fa-clipboard-check"></i> <span data-i18n="Panel">Panel</span></a>
                </div></details>
            @endauth
        </div>
    </nav></header>

    <main class="content-wrap">
        <section class="hero-banner"><h1>SPInE Student Project System</h1><p>JTMK | DFT50114 Integrated Project</p><span class="yellow-badge">Politeknik Besut</span></section>

        <section class="white-card" aria-labelledby="ranking-title"><div class="section-heading"><h2 id="ranking-title"><i class="fa-solid fa-trophy gold"></i> <span data-i18n="Top 5 Project Ranking">Top 5 Project Ranking</span></h2><span class="small-badge blue-badge" data-i18n="Demo 3 Panel Marks">Demo 3 Panel Marks</span></div>
            @if($topRankings->isEmpty())<p class="empty-text" data-i18n="Ranking empty">Project ranking will appear here once the external panel submits Demo 3 marks.</p>
            @else
                <div class="ranking-podium">@foreach([2, 1, 3] as $position) @if($topRankings->has($position - 1)) @php($ranking = $topRankings[$position - 1])
                    <div class="podium-step podium-step-{{ $position }}"><article class="podium-card"><span class="podium-medal">#{{ $position }}</span><h3 title="{{ $ranking->title }}">{{ $ranking->title }}</h3><p>Group {{ $ranking->project_group_no }} · {{ $ranking->leader_name ?: '-' }}</p><span class="small-badge blue-badge">{{ number_format($ranking->avg_total_score, 0) }} pts</span></article><div class="podium-bar podium-bar-{{ $position }}"></div></div>
                @endif @endforeach</div>
                @if($topRankings->count() > 3)<div class="ranking-list">@foreach($topRankings->slice(3) as $ranking)<div><span class="small-badge gray-badge">#{{ $loop->iteration + 3 }}</span> <strong>{{ $ranking->title }}</strong><small>Group {{ $ranking->project_group_no }} · {{ $ranking->leader_name ?: '-' }}</small><span class="small-badge blue-badge">{{ number_format($ranking->avg_total_score, 0) }} pts</span></div>@endforeach</div>@endif
            @endif
        </section>

        <section class="white-card" aria-labelledby="choices-title"><div class="section-heading"><h2 id="choices-title"><i class="fa-solid fa-star gold"></i> <span data-i18n="Panel's Choices">Panel's Choices</span></h2><span class="small-badge yellow-badge" data-i18n="Featured Projects">Featured Projects</span></div>
            @if($panelChoices->isEmpty())<p class="empty-text" data-i18n="Choices empty">Panel's Choices will appear here after the panel selects the featured groups.</p>
            @else <div class="tile-grid">@foreach($panelChoices as $choice)<article class="data-tile"><span class="small-badge blue-badge">Group {{ $choice->project_group_no }}</span><h3>{{ $choice->title }}</h3><p>{{ $choice->leader_name ?: '-' }} · {{ $choice->member_count }} students · {{ $choice->session ?: '-' }}</p></article>@endforeach</div>@endif
        </section>

        <section class="announcement" role="status"><i class="fa-solid fa-bullhorn"></i><p><strong data-i18n="Current Announcement:">Current Announcement:</strong> {{ $announcement }}</p></section>

        <section class="white-card" aria-labelledby="deadlines-title"><div class="section-heading"><h2 id="deadlines-title"><i class="fa-solid fa-calendar-check"></i> <span data-i18n="Project Deadlines">Project Deadlines</span></h2></div>
            @if($deadlines->isEmpty())<p class="empty-text" data-i18n="Deadlines empty">No project deadlines have been published yet.</p>
            @else <div class="tile-grid deadlines-grid">@foreach($deadlines as $deadline)<article class="data-tile"><h3>{{ $deadline->title }}</h3>
                @if($deadline->due_date && !str_starts_with($deadline->due_date, '0000-00-00'))<span class="small-badge {{ \Illuminate\Support\Carbon::parse($deadline->due_date)->isPast() ? 'gray-badge' : 'yellow-badge' }}"><i class="fa-regular fa-clock"></i> {{ \Illuminate\Support\Carbon::parse($deadline->due_date)->format('d/m/Y, h:i A') }}</span>
                @else <span class="small-badge cyan-badge" data-i18n="To Be Announced">To Be Announced</span>@endif
                <p>{{ $deadline->description }}</p></article>@endforeach</div>@endif
        </section>

        <section class="projects-section" aria-labelledby="projects-title"><h2 id="projects-title"><i class="fa-solid fa-spinner gold"></i> <span data-i18n="Current Projects In Progress">Current Projects In Progress</span></h2>
            @if($currentProjects->isEmpty())<div class="white-card empty-projects"><i class="fa-regular fa-folder-open"></i><p data-i18n="Projects empty">Current session project materials are being updated by students.</p></div>
            @else <div class="tile-grid">@foreach($currentProjects as $project)<article class="white-card project-card"><span class="small-badge gray-badge">{{ $project->department ?: 'N/A' }} - {{ $project->category ?: 'N/A' }}</span><h3>{{ $project->title }}</h3><p>{{ \Illuminate\Support\Str::limit($project->description ?? '', 120) }}</p><small><i class="fa-solid fa-user"></i> Student: {{ $project->full_name }}</small></article>@endforeach</div>@endif
        </section>
    </main>
    <footer class="site-footer">&copy; {{ date('Y') }} <strong>SPInE FYP</strong> | Politeknik Besut, Terengganu. Hak Cipta Terpelihara.</footer>
</body></html>
