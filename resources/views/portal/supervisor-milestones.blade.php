@extends('layouts.app')
@section('title', 'Demo Milestone Status')
@section('content')
<div class="supervisor-page-heading supervisor-milestone-heading"><div><h1><i class="fa-solid fa-flag-checkered"></i> Demo Milestone Status</h1><p>Update Passed or Not Passed status only. No numerical marks are entered.</p></div><a class="button secondary" href="{{ route('supervisor.students.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to Students</a></div>
<section class="supervisor-student-summary"><strong>{{ $student->full_name }}</strong><small>Matrix No: {{ $student->matric_no ?: '—' }} | Session: {{ $student->session ?: '—' }}</small></section>
<section class="panel-card supervisor-milestone-card"><form method="post" action="{{ route('supervisor.students.milestones.update', $student->id) }}">@csrf @method('PATCH')
    <div class="supervisor-info-note"><i class="fa-solid fa-shield-halved"></i> These are milestone verification statuses, not grades or marks.</div>
    <div class="supervisor-milestone-fields">
        <label for="demo-1">Demo 1 Status<select id="demo-1" name="demo_1"><option @selected(($verification['Demo 1'] ?? 'Pending') === 'Pending')>Pending</option><option @selected(($verification['Demo 1'] ?? '') === 'Passed')>Passed</option><option @selected(($verification['Demo 1'] ?? '') === 'Not Passed')>Not Passed</option></select></label>
        <label for="demo-2">Demo 2 Status<select id="demo-2" name="demo_2"><option @selected(($verification['Demo 2'] ?? 'Pending') === 'Pending')>Pending</option><option @selected(($verification['Demo 2'] ?? '') === 'Passed')>Passed</option><option @selected(($verification['Demo 2'] ?? '') === 'Not Passed')>Not Passed</option></select></label>
    </div>
    <div class="supervisor-milestone-actions"><button class="button primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Status</button></div>
</form></section>
@endsection
