@extends('layouts.app')
@section('title', 'Review Documents')
@section('content')
<div class="supervisor-page-heading"><div><h1><i class="fa-solid fa-file-lines"></i> Review Documents</h1><p>Read-only document review for JTMK projects. No numerical marks or grades are entered here.</p></div></div>
<section class="supervisor-review-list"><h2>Supervised Projects</h2>@forelse($projects as $project)<a class="supervisor-review-project-link" href="{{ route('supervisor.documents.project', $project->id) }}"><span><strong>{{ $project->title }}</strong><small>{{ $project->category }} | {{ $project->session }}</small></span><i class="fa-solid fa-chevron-right"></i></a>@empty<div class="supervisor-empty">No supervised projects found.</div>@endforelse</section>
@endsection
