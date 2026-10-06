@extends('layouts.app')
@section('title', 'Supervisor dashboard')
@section('content')
<div class="supervisor-welcome">
    <div><span class="eyebrow"><i class="fa-solid fa-user-tie"></i> SUPERVISOR PORTAL</span><h1>Welcome back, {{ auth()->user()->full_name }}! <span aria-hidden="true">👋</span></h1><p>Here is the overview of your supervision activities, student progress, and final year project updates for this session.</p></div>
    <span class="supervisor-date"><i class="fa-regular fa-calendar-days"></i> {{ now()->format('F d, Y') }}</span>
</div>
<section class="supervisor-banner"><h2><i class="fa-solid fa-chalkboard-user"></i> Supervisor Dashboard Control Center</h2><p>Manage your supervised students, review pending project submissions, and evaluate academic milestones efficiently in one centralized place.</p></section>
<section class="supervisor-stats"><a class="supervisor-stat" href="{{ route('supervisor.students.index') }}"><span class="supervisor-stat-icon blue"><i class="fa-solid fa-user-graduate"></i></span><span><small>TOTAL STUDENTS</small><strong>{{ $students->count() }}</strong><em>Total individual students across all groups</em></span></a><a class="supervisor-stat" href="{{ route('supervisor.projects.index') }}"><span class="supervisor-stat-icon green"><i class="fa-solid fa-diagram-project"></i></span><span><small>SUPERVISED GROUPS</small><strong>{{ $projects->count() }}</strong><em>Total groups/projects under your supervision</em></span></a></section>
<section class="panel-card supervisor-recent">
    <div class="section-head"><h2><i class="fa-solid fa-list-alt"></i> Recent Student Submissions &amp; Groups</h2><a class="button compact secondary" href="{{ route('supervisor.projects.index') }}">View All</a></div>
    <div class="table-scroll"><table><thead><tr><th>Project Title &amp; Group Members</th><th>Submission Date</th><th>Status</th><th>Department</th></tr></thead><tbody>
    @forelse($projects->sortByDesc('created_at')->take(8) as $project)
        @php($projectMembers = $members->get($project->id, collect()))
        @php($latestSubmission = $documents->where('project_id', $project->id)->sortByDesc('uploaded_at')->first())
        <tr><td><a class="supervisor-project-link" href="{{ route('supervisor.projects.index') }}"><i class="fa-solid fa-diagram-project"></i> {{ $project->title }}</a><small class="supervisor-member-label">Group Members:</small><div class="supervisor-member-chips">@forelse($projectMembers as $member)<span class="group-member-chip {{ str_contains(strtolower($member->role ?? ''), 'leader') ? 'leader' : '' }}"><i class="fa-solid fa-user"></i>{{ $member->full_name }} ({{ $member->matric_no }}) @if(str_contains(strtolower($member->role ?? ''), 'leader')) [Leader] @endif</span>@empty<span class="group-member-chip leader"><i class="fa-solid fa-user"></i>{{ $project->leader_name ?: 'Member list unavailable' }}</span>@endforelse</div></td><td>{{ $latestSubmission?->uploaded_at ? \Illuminate\Support\Carbon::parse($latestSubmission->uploaded_at)->format('d M Y, h:i A') : '—' }}</td><td><span class="supervisor-status {{ strtolower($latestSubmission?->status ?? $project->status ?? 'pending') }}">{{ $latestSubmission?->status ?? $project->status ?? 'Pending' }}</span></td><td>{{ $project->department ?: 'JTMK' }}</td></tr>
    @empty<tr><td colspan="4" class="supervisor-empty">No supervised projects found.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
