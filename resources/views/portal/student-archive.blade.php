@extends('layouts.app')
@section('title', 'Past Projects Archive')
@section('content')
<section class="panel-card"><h1>Past Projects Archive</h1><p>Browse project references across sessions.</p><form class="student-search" method="get" action="{{ route('student.archive.index') }}"><label>Search projects<input name="search" value="{{ $search }}" placeholder="Search title or description..."></label><button class="button primary" type="submit">Search</button><a class="button secondary" href="{{ route('student.archive.index') }}">Reset</a></form></section>
<div class="student-archive-grid">@forelse($projects as $project)<article class="panel-card"><small>{{ $project->project_group_no ? 'Group '.$project->project_group_no.' · ' : '' }}{{ $project->session }}</small><h2>{{ $project->title }}</h2><p>{{ $project->description ?: 'No description provided.' }}</p><p><strong>Category:</strong> {{ $project->category ?: '–' }}</p><p><strong>Leader:</strong> {{ $project->leader_name ?: '–' }}</p></article>@empty<section class="panel-card"><h2>No reference projects found</h2></section>@endforelse</div>
@endsection
