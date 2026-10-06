@extends('layouts.app')
@section('title', 'Manage JTMK Projects')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-projects.css') }}?v=1">
<div class="admin-projects-page">
    <div class="page-heading admin-projects-heading">
        <div><h1><i class="fa-solid fa-rectangle-list"></i> Manage JTMK Projects</h1><p>Assign supervisors and monitor Demo 1/Demo 2 verification statuses.</p></div>
        <a class="button outline" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @forelse($projects as $project)
        <article class="project-admin-card">
            <div class="project-admin-header">
                <div class="project-admin-title">
                    <h2>@if($project->project_group_no)<span class="project-group-badge">Group {{ $project->project_group_no }}</span>@endif{{ $project->title }}</h2>
                    <div class="project-admin-meta"><span class="project-category">{{ $project->category }}</span><span>Session: {{ $project->session }}</span></div>
                </div>
                <div class="project-admin-state">
                    <span class="project-status {{ strtolower($project->status ?: 'submitted') }}">{{ $project->status ?: 'Submitted' }}</span>
                    @if($project->status === 'Submitted')
                        <form method="post" action="{{ route('admin.projects.update', $project->id) }}" onsubmit="return confirm('Approve this project?')">@csrf @method('PATCH')<input type="hidden" name="status" value="Approved"><button class="button approve-button" type="submit"><i class="fa-solid fa-check"></i> Approve</button></form>
                    @endif
                </div>
            </div>

            <div class="project-admin-grid">
                <section class="project-members-section">
                    <h3>Group Members</h3>
                    @if($project->members->isNotEmpty())
                        <ul class="project-member-list">
                            @foreach($project->members as $member)
                                <li>{{ $member->full_name }} <span>({{ $member->matric_no ?: $member->ic_number }})</span>@if($member->member_role === 'Leader')<b>Leader</b>@endif</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="project-empty-note">No group members are linked to this project.</p>
                    @endif
                </section>

                <section class="project-assignment-section">
                    <h3>Supervisor Assignment</h3>
                    <form class="supervisor-assignment-form" method="post" action="{{ route('admin.projects.supervisor', $project->id) }}">
                        @csrf @method('PATCH')
                        <label class="sr-only" for="supervisor-{{ $project->id }}">Select JTMK supervisor</label>
                        <select id="supervisor-{{ $project->id }}" name="supervisor_id" required>
                            <option value="">-- Select JTMK Supervisor --</option>
                            @foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}" @selected((int)$project->supervisor_id === (int)$supervisor->id)>{{ $supervisor->full_name }}</option>@endforeach
                        </select>
                        <button class="button assign-button" type="submit"><i class="fa-solid fa-user-check"></i> Assign SV</button>
                    </form>
                    <p class="current-supervisor">Current Supervisor: <strong>{{ $project->supervisor_name ?: 'Not Assigned Yet' }}</strong></p>

                    <h3 class="milestone-table-title">Individual Milestone Verification</h3>
                    <div class="project-status-table-wrap"><table class="project-status-table"><thead><tr><th>Student</th><th>Demo 1</th><th>Demo 2</th></tr></thead><tbody>
                        @forelse($project->members as $member)
                            <tr><td>{{ $member->full_name }}</td><td><span class="milestone-pill {{ str($member->demo1_status)->slug() }}">{{ $member->demo1_status }}</span></td><td><span class="milestone-pill {{ str($member->demo2_status)->slug() }}">{{ $member->demo2_status }}</span></td></tr>
                        @empty<tr><td colspan="3" class="project-empty-note">No milestone status available.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
            </div>
        </article>
    @empty
        <section class="project-admin-empty"><i class="fa-solid fa-folder-open"></i><h2>No JTMK projects found</h2><p>Projects registered for JTMK will appear here.</p></section>
    @endforelse
</div>
@endsection
