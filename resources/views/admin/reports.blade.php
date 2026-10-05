@extends('layouts.app')
@section('title', 'Project reports')
@section('content')
<div class="page-heading"><div><span class="eyebrow">ADMINISTRATION</span><h1>Project reports</h1><p>Live summaries from registered projects and recorded marks.</p></div></div>
<div class="content-grid"><section class="panel-card"><span class="eyebrow">PROJECT COUNTS</span><h2>By category</h2>@forelse($byCategory as $row)<div class="report-row"><span>{{ $row->category }}</span><strong>{{ $row->project_count }}</strong></div>@empty<p class="empty-state">No project data is available.</p>@endforelse</section><section class="panel-card"><span class="eyebrow">WORKFLOW</span><h2>By status</h2>@forelse($byStatus as $row)<div class="report-row"><span>{{ $row->status }}</span><strong>{{ $row->project_count }}</strong></div>@empty<p class="empty-state">No project data is available.</p>@endforelse</section></div>
<section class="panel-card"><span class="eyebrow">RECORDED SCORES</span><h2>Projects by latest total</h2><div class="table-scroll"><table><thead><tr><th>Project</th><th>Session</th><th>Total</th></tr></thead><tbody>@foreach($topMarks as $row)<tr><td>{{ $row->title }}</td><td>{{ $row->session }}</td><td>{{ $row->total_score === null ? 'No mark recorded' : number_format((float)$row->total_score,2) }}</td></tr>@endforeach</tbody></table></div></section>
@endsection
