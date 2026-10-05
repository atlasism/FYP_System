@extends('layouts.app')
@section('title', 'Register a project')
@section('content')
<div class="page-heading"><div><span class="eyebrow">STUDENT WORKSPACE</span><h1>Register your project</h1><p>Project leaders can include up to two teammates by their matric number.</p></div><a class="button secondary" href="{{ route('student.dashboard') }}">Back to dashboard</a></div>
<form class="panel-card project-form" method="post" action="{{ route('student.projects.store') }}">@csrf
<div class="form-row"><label>Project title<input name="title" value="{{ old('title') }}" required maxlength="255"></label><label>Academic session<input name="session" value="{{ old('session', 'Sesi 1 2026/2027') }}" required maxlength="50"></label></div>
<label>Project category<select name="category" required><option value="">Choose a category</option>@foreach($categories as $category)<option @selected(old('category') === $category)>{{ $category }}</option>@endforeach</select></label>
<label>Project description<textarea name="description" rows="5" required maxlength="10000">{{ old('description') }}</textarea></label>
<div class="form-row"><label>Teammate 2 matric number (optional)<input name="member_2" value="{{ old('member_2') }}" maxlength="30"></label><label>Teammate 3 matric number (optional)<input name="member_3" value="{{ old('member_3') }}" maxlength="30"></label></div>
<button class="button primary" type="submit">Register project and team</button></form>
@endsection
