@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-reports.css') }}?v=1">
<div class="admin-reports-page">
    <div class="admin-reports-heading">
        <div><h1><i class="fa-solid fa-chart-column"></i> Reports</h1><p>JTMK and DFT50114 project administration reports.</p></div>
    </div>

    <div class="reports-overview-grid">
        <section class="report-card">
            <h2>Projects by Category</h2>
            <div class="report-table-wrap"><table class="report-table"><thead><tr><th>Category</th><th class="report-number">Projects</th></tr></thead><tbody>
                @forelse($byCategory as $row)<tr><td>{{ $row->category ?: 'Uncategorized' }}</td><td class="report-number"><span class="report-count-pill">{{ $row->project_count }}</span></td></tr>
                @empty<tr><td colspan="2" class="report-empty-cell">No projects found.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section class="report-card">
            <h2>Project Registration Status</h2>
            @forelse($byStatus as $row)<div class="report-status-row"><span>{{ $row->status ?: 'Unknown' }}</span><strong>{{ $row->project_count }}</strong></div>
            @empty<p class="report-empty">No project status data is available.</p>@endforelse
        </section>
    </div>

    <section class="report-card report-demo-card">
        <h2>Demo Verification Summary</h2>
        <div class="report-demo-grid">
            @foreach(['Demo 1', 'Demo 2'] as $demo)
                @php($statuses = $demoSummary->get($demo, collect()))
                <div class="report-demo-tile"><h3>{{ $demo }}</h3><div class="report-status-pills">
                    <span class="report-pill passed">Passed: {{ $statuses->get('Passed', 0) }}</span>
                    <span class="report-pill not-passed">Not Passed: {{ $statuses->get('Not Passed', 0) }}</span>
                    <span class="report-pill pending">Pending: {{ $statuses->get('Pending', 0) }}</span>
                </div></div>
            @endforeach
        </div>
        <p class="report-caption">This summary covers Demo 1 and Demo 2 verification. Demo 3 panel scores are listed below.</p>
    </section>

    <section class="report-card" id="project-ranking">
        <div class="report-section-heading"><div><h2><i class="fa-solid fa-trophy"></i> Top 5 Project Ranking by QR Batch</h2><p>Top groups ranked by average Demo 3 marks for the selected QR batch.</p></div><span class="report-count-pill">{{ $projectRanking->count() }} ranked {{ \Illuminate\Support\Str::plural('group', $projectRanking->count()) }}</span></div>
        @if($panelBatches->isEmpty())
            <div class="report-info">No evaluated QR batches are available yet.</div>
            <p class="report-caption report-bottom-caption">Ranking will appear after a panel evaluates a project through a QR batch.</p>
        @else
            <form class="report-batch-filter" method="get" action="{{ route('admin.reports') }}">
                <label for="panel_batch">QR batch</label>
                <select name="panel_batch" id="panel_batch">
                    @foreach($panelBatches as $batch)
                        <option value="{{ $batch->id }}" @selected((int) $selectedBatch?->id === (int) $batch->id)>{{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('d M Y, h:i A') }} · Groups {{ $batch->first_group_no ?? '—' }}–{{ $batch->last_group_no ?? '—' }}</option>
                    @endforeach
                </select>
                <button class="report-button" type="submit"><i class="fa-solid fa-filter"></i> Show Ranking</button>
            </form>
            @if($projectRanking->isEmpty())
                <div class="report-info">No evaluated groups are available in this QR batch.</div>
            @else
                <div class="report-table-wrap"><table class="report-table"><thead><tr><th>Rank</th><th>Group</th><th>Project</th><th>Leader</th><th>Panel Submissions</th><th>Average Marks /100</th><th>Average Demo 3 /15</th></tr></thead><tbody>
                    @foreach($projectRanking as $ranking)<tr><td><span class="report-rank {{ $loop->iteration <= 3 ? 'top' : '' }}">#{{ $loop->iteration }}</span></td><td>{{ $ranking->project_group_no ?: '—' }}</td><td><strong>{{ $ranking->title }}</strong><small>{{ $ranking->category }}</small></td><td>{{ $ranking->leader_name ?: '—' }}</td><td>{{ $ranking->evaluation_count }}</td><td><strong>{{ number_format((float) $ranking->average_score, 2) }}</strong></td><td>{{ number_format((float) $ranking->average_demo3_score, 2) }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        @endif
    </section>

    <section class="report-card" id="panel-choices">
        <div class="report-section-heading"><div><h2><i class="fa-solid fa-star"></i> Panel's Choices</h2><p>Panel favourites from the same QR batch selected above.</p></div><span class="report-choice-count">{{ $panelChoices->count() }} selected</span></div>
        @if($panelBatches->isNotEmpty())
            <form class="report-batch-filter" method="get" action="{{ route('admin.reports') }}">
                <label for="panel_batch_choices">QR batch</label>
                <select name="panel_batch" id="panel_batch_choices">
                    @foreach($panelBatches as $batch)
                        <option value="{{ $batch->id }}" @selected((int) $selectedBatch?->id === (int) $batch->id)>{{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('d M Y, h:i A') }} · Groups {{ $batch->first_group_no ?? '—' }}–{{ $batch->last_group_no ?? '—' }}</option>
                    @endforeach
                </select>
                <button class="report-button" type="submit"><i class="fa-solid fa-filter"></i> Show Choices</button>
            </form>
        @endif
        @if($panelChoices->isEmpty())<p class="report-empty report-bottom-caption">No groups have been selected in this QR batch.</p>
        @else<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Group</th><th>Project</th><th>Category</th><th>Session</th><th>Leader</th></tr></thead><tbody>
            @foreach($panelChoices as $choice)<tr><td>{{ $choice->project_group_no ?: '—' }}</td><td>{{ $choice->title }}</td><td>{{ $choice->category }}</td><td>{{ $choice->session }}</td><td>{{ $choice->leader_name ?: '—' }}</td></tr>@endforeach
        </tbody></table></div>@endif
    </section>

    <section class="report-card report-evaluations-card">
        <div class="report-section-heading"><div><h2>External Panel Demo 3 Evaluations</h2><p>One row per group from the latest generated QR batch.</p></div><span class="report-count-pill">{{ $panelSubmissionCount }} submissions · {{ $panelReportGroups->count() }} groups</span></div>
        @if($panelReportGroups->isEmpty())<p class="report-empty">No groups are available in the latest QR batch.</p>
        @else<div class="report-table-wrap"><table class="report-table report-evaluations-table"><thead><tr><th>Group</th><th>Project</th><th>Panel Submissions</th><th>Average Rating /4</th><th>Average Demo 3 /15</th><th>Details</th></tr></thead><tbody>
            @foreach($panelReportGroups as $group)
                <tr><td>{{ $group->group_no ?: '—' }}</td><td>{{ $group->title }}</td><td>{{ $group->evaluations->count() }}</td><td>{{ $group->average_rating === null ? 'Not Evaluated' : number_format($group->average_rating, 2) }}</td><td>{{ $group->average_demo3 === null ? 'Not Evaluated' : number_format($group->average_demo3, 2) }}</td><td>
                    @if($group->evaluations->isNotEmpty())<details class="report-breakdown"><summary>Panel breakdown</summary>
                        @foreach($group->evaluations as $evaluation)
                            <article class="report-evaluator-card"><div class="report-evaluator-heading"><div><strong>{{ $evaluation->panel_name }}</strong>@if($evaluation->panel_email)<small>{{ $evaluation->panel_email }}</small>@endif<small>{{ $evaluation->assessor_types ?: 'External Assessor' }} · {{ \Illuminate\Support\Carbon::parse($evaluation->created_at)->format('d M Y, h:i A') }}</small></div><span>Rating {{ number_format((float) $evaluation->average_score, 2) }}/4 · Demo 3 {{ number_format((float) ($evaluation->group_demo3_score ?? 0), 2) }}/15</span></div>
                                <div class="report-table-wrap"><table class="report-table report-detail-table"><thead><tr><th>Student</th><th>Matric</th>@foreach(range(1, 8) as $criterion)<th>Criteria {{ $criterion }}</th>@endforeach<th>Total /100</th><th>Demo 3 /15</th></tr></thead><tbody>
                                    @foreach($evaluation->members as $member)
                                        @php($memberId = (string) ($member['id'] ?? ''))
                                        <tr><td>{{ $member['full_name'] ?? '—' }}</td><td>{{ $member['matric_no'] ?? '—' }}</td>
                                            @foreach(range(0, 7) as $criterionIndex)<td>{{ data_get($evaluation->aspects, $criterionIndex.'.'.$memberId, '—') }}</td>@endforeach
                                            <td><strong>{{ number_format((float) data_get($evaluation->results, $memberId.'.total_score', 0), 2) }}</strong></td><td><strong>{{ number_format((float) data_get($evaluation->results, $memberId.'.demo3_score', 0), 2) }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody></table></div>
                                @if($evaluation->comments)<p class="report-panel-comment"><strong>Panel Comments:</strong> {!! nl2br(e($evaluation->comments)) !!}</p>@endif
                            </article>
                        @endforeach
                    </details>@else<span class="report-muted">-</span>@endif
                </td></tr>
            @endforeach
        </tbody></table></div>@endif
    </section>
</div>
@endsection
