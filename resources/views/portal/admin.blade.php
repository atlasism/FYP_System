@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="admin-dashboard-heading"><div><h1><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</h1><p>JTMK - DFT50114 Integrated Project administration.</p></div><span class="admin-department-badge">JTMK | DFT50114</span></div>

<section class="admin-summary-grid" aria-label="Department summary">
    <article class="admin-summary-card students"><div><small>JTMK Students</small><strong>{{ number_format($stats['students']) }}</strong></div><i class="fa-solid fa-user-graduate"></i></article>
    <article class="admin-summary-card supervisors"><div><small>Supervisors / Lecturers</small><strong>{{ number_format($stats['supervisors']) }}</strong></div><i class="fa-solid fa-chalkboard-user"></i></article>
    <article class="admin-summary-card projects"><div><small>Registered Projects</small><strong>{{ number_format($stats['projects']) }}</strong></div><i class="fa-solid fa-book-open"></i></article>
</section>

<section class="panel-card admin-status-panel"><h2><i class="fa-solid fa-chart-pie"></i> Overall Project Status</h2><div class="admin-status-grid">@foreach($statusCounts as $label => $count)<div class="admin-status-tile {{ strtolower($label) }}"><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong></div>@endforeach</div></section>

<section class="panel-card admin-ranking-panel"><div class="admin-panel-heading"><div><h2><i class="fa-solid fa-trophy"></i> Top 5 Project Ranking</h2><p>Ranked by average panel marks from Demo 3 evaluation.</p></div><a class="button secondary compact" href="{{ route('admin.reports') }}#project-ranking"><i class="fa-solid fa-list-ol"></i> View Full Ranking</a></div>
    @if($rankings->isEmpty())<p class="admin-empty-ranking">Ranking will appear once the external panel submits marks.</p>@else<div class="table-scroll"><table class="admin-ranking-table"><thead><tr><th>Rank</th><th>Project</th><th>Group</th><th>Average Score</th></tr></thead><tbody>@foreach($rankings as $ranking)<tr><td><span class="admin-rank-number">{{ $loop->iteration }}</span></td><td><strong>{{ $ranking->title }}</strong></td><td>{{ $ranking->project_group_no ? 'Group '.$ranking->project_group_no : '—' }}</td><td><strong>{{ number_format((float) $ranking->average_score, 2) }}</strong></td></tr>@endforeach</tbody></table></div>@endif
</section>

<section class="panel-card admin-choices-panel"><div class="admin-panel-heading"><div><h2><i class="fa-solid fa-star"></i> Panel's Choices</h2><p>Groups selected by the external panel.</p></div><a class="button secondary compact" href="{{ route('admin.reports') }}#panel-choices"><i class="fa-solid fa-chart-column"></i> Open Panel's Choice Report</a></div><div class="admin-choice-count"><strong>{{ number_format($panelChoices->count()) }}</strong><span>selected {{ \Illuminate\Support\Str::plural('group', $panelChoices->count()) }}</span></div>
    @if($panelChoices->isNotEmpty())<div class="admin-choice-list">@foreach($panelChoices->take(5) as $choice)<div><strong>{{ $choice->title }}</strong><small>Group {{ $choice->project_group_no ?: '—' }} · {{ $choice->session ?: 'Session not set' }}</small></div>@endforeach</div>@endif
</section>
@endsection
