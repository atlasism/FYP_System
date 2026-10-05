@extends('layouts.app')
@section('title', 'Home')
@section('content')
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">POLITEKNIK BESUT · JTMK</span>
        <h1>Final year projects,<br><em>all in one place.</em></h1>
        <p>Register project teams, submit work, follow supervisor feedback, and prepare for assessment through SPInE.</p>
        <div class="actions"><a class="button primary" href="{{ route('login') }}">Sign in to SPInE</a><a class="button secondary" href="{{ route('register') }}">Create a student account</a></div>
    </div>
    <div class="hero-card"><div class="orb">S</div><span class="eyebrow">PROJECT WORKSPACE</span><h2>From proposal to presentation</h2><p>Keep your project team, submissions, and assessment information together through the academic session.</p><div class="hero-metrics"><div><strong>{{ number_format($projects) }}</strong><small>approved projects</small></div><div><strong>{{ number_format($students) }}</strong><small>student accounts</small></div></div></div>
</section>
@if ($announcement)<section class="announcement"><span class="eyebrow">ANNOUNCEMENT</span><p>{{ $announcement }}</p></section>@endif
<section class="feature-grid">
    <article class="feature"><span class="feature-index">01</span><h3>Project workspace</h3><p>Coordinate a project group and keep the latest submissions in one place.</p></article>
    <article class="feature"><span class="feature-index">02</span><h3>Supervisor review</h3><p>Follow document status and assessment milestones throughout the project.</p></article>
    <article class="feature"><span class="feature-index">03</span><h3>Panel assessment</h3><p>Bring final presentation and evaluation activity into the same system.</p></article>
</section>
@endsection
