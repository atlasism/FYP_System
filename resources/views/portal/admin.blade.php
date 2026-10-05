@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('content')
<div class="page-heading"><div><span class="eyebrow">ADMINISTRATION</span><h1>System overview</h1><p>Current users, project activity, and review workload.</p></div></div>
@include('admin.nav')
<section class="stat-grid">@foreach([['Student accounts',$stats['students']],['Supervisors',$stats['supervisors']],['Projects',$stats['projects']],['Pending documents',$stats['pending_documents']]] as [$label,$value])<div class="stat-card"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></div>@endforeach</section>
<div class="content-grid"><section class="panel-card"><span class="eyebrow">PROJECT REGISTER</span><h2>Latest projects</h2>@if($projects->isEmpty())<p class="empty-state">No projects have been registered yet.</p>@else<div class="table-scroll"><table><thead><tr><th>Project</th><th>Category</th><th>Session</th><th>Status</th></tr></thead><tbody>@foreach($projects as $project)<tr><td>{{ $project->title }}</td><td>{{ $project->category }}</td><td>{{ $project->session }}</td><td>{{ $project->status }}</td></tr>@endforeach</tbody></table></div>@endif</section>
<aside class="panel-card"><span class="eyebrow">SCHEDULE</span><h2>Submission deadlines</h2>@forelse($deadlines as $deadline)<div class="deadline"><strong>{{ $deadline->title }}</strong><small>{{ $deadline->due_date && !str_starts_with($deadline->due_date, '0000-00-00') ? \Illuminate\Support\Carbon::parse($deadline->due_date)->format('d M Y, g:i A') : 'Date to be announced' }}</small></div>@empty<p class="muted">No deadlines have been posted.</p>@endforelse</aside></div>
@endsection
