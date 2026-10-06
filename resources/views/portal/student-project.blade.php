@extends('layouts.app')
@section('title', 'My Project Information')
@section('content')
<div class="legacy-heading"><div><h1><i class="fa-solid fa-diagram-project"></i> My Project Information</h1><p>Manage your Final Year Project / SPInE details.</p></div></div>
@if($project)<section class="panel-card student-project-page"><span class="group-status">You Are Already in a Group</span><h2>{{ $project->title }}</h2><p><strong>Category:</strong> {{ $project->category }} | <strong>Your Role:</strong> <span class="role-status">{{ $team->firstWhere('id', auth()->id())->role ?? 'Member' }}</span></p><p>{{ $project->description }}</p><hr><h3>Project Group Members (Max 3 Members):</h3><ul>@forelse($team as $member)<li><i class="fa-solid fa-circle-user"></i> <strong>{{ $member->full_name }}</strong> ({{ $member->ic_number ?: $member->matric_no }}) <span class="member-role">- {{ $member->role }}</span></li>@empty<li>No members are listed yet.</li>@endforelse</ul></section>
@else<section class="panel-card"><h2>No project registered yet</h2><p>Register your project and team to begin submitting documents.</p><a class="button primary" href="{{ route('student.projects.create') }}">Register Project</a></section>@endif
@endsection
