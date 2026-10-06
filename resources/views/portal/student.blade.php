@extends('layouts.app')
@section('title', 'Student dashboard')
@section('content')
<div class="legacy-heading"><div><h1>Welcome, {{ $user->full_name }}! 👋</h1><p>Here is the summary of your project progress and document submissions.</p></div>@unless($project)<a class="button primary" href="{{ route('student.projects.create') }}"><i class="fa-solid fa-circle-plus"></i> Register Project</a>@endunless</div>
<div class="student-status-grid">
    <div class="student-status-card status-warning"><div><small>PROJECT STATUS</small><strong><span class="project-state-pill {{ !$project ? 'unregistered' : ($project->is_complete_for_evaluation ? 'complete' : 'in-progress') }}">@if(!$project)Not Registered@elseif($project->is_complete_for_evaluation)Complete & Ready@else In Progress @endif</span></strong></div><i class="fa-solid fa-list-check"></i></div>
    <div class="student-status-card status-success"><div><small>TOTAL SCORE</small><strong>{{ $totalScore === null ? 'Not Evaluated Yet' : number_format((float) $totalScore, 0).' / 100' }}</strong></div><i class="fa-solid fa-star"></i></div>
    <div class="student-status-card status-blue"><div><small>PROJECT RANK</small><strong>–</strong></div><i class="fa-solid fa-trophy"></i></div>
</div>
<section class="panel-card student-document-status" id="submissions">
    <div class="student-card-heading"><h2><i class="fa-solid fa-file-arrow-up"></i> Document Submission Status</h2><a class="button secondary compact" href="{{ route('student.documents.index') }}"><i class="fa-solid fa-upload"></i> Upload File</a></div>
    @php($documentTypes = ['A' => 'A: Proposal Presentation (10%)', 'B' => 'B: Project Demonstration 1 (10%)', 'C' => 'C: Project Demonstration 2 (10%)', 'D' => 'D: Project Demonstration 3 (15%)', 'E' => 'E: Final Presentation - Poster (15%)', 'F' => 'F: Final Presentation (15%)', 'TECHNICAL_REPORT' => 'Technical Report (15%)'])
    <div class="student-document-grid">@foreach($documentTypes as $code => $label)
        @php($uploaded = $documents->contains(fn ($document) => strcasecmp(trim($document->doc_type), $code) === 0))
        <div class="student-document-item"><div><strong>{{ $label }}</strong><small class="{{ $uploaded ? 'uploaded' : 'missing' }}"><i class="fa-solid {{ $uploaded ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i> {{ $uploaded ? 'Uploaded' : 'Not Uploaded' }}</small></div><span class="document-indicator {{ $uploaded ? 'uploaded' : '' }}"><i class="fa-solid {{ $uploaded ? 'fa-check' : 'fa-clock' }}"></i></span></div>
    @endforeach</div>
</section>
@if($project)<section class="panel-card student-project-details" id="project"><h2><i class="fa-solid fa-circle-info"></i> Project Summary Information</h2><div class="student-project-columns"><div><p><strong>Project Title:</strong> {{ $project->title }}</p><p><strong>Supervisor:</strong> {{ $supervisor ?: 'Not Assigned Yet' }}</p><p><strong>Short Description:</strong> {{ $project->description ?: 'No description provided.' }}</p></div><div><p><strong>Category:</strong> {{ $project->category ?: '–' }}</p><p><strong>Activity Session:</strong> {{ $project->session ?: 'Current Session' }}</p><p><strong>Group:</strong> {{ $project->project_group_no ? 'Group '.$project->project_group_no : 'Not assigned' }}</p></div></div></section>@endif
<section class="panel-card student-deadlines" id="deadlines"><h2><i class="fa-solid fa-calendar-days"></i> Deadline Reminders</h2>@forelse($deadlines as $deadline)<div class="deadline"><strong>{{ $deadline->title }}</strong><small>{{ $deadline->due_date && !str_starts_with($deadline->due_date, '0000-00-00') ? \Illuminate\Support\Carbon::parse($deadline->due_date)->format('d M Y, g:i A') : 'Date to be announced' }}</small></div>@empty<p>No deadlines have been posted.</p>@endforelse</section>
@endsection
