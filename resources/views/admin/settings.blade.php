@extends('layouts.app')
@section('title', 'System settings')
@section('content')
<div class="page-heading"><div><span class="eyebrow">ADMINISTRATION</span><h1>System settings</h1><p>Update announcements and application preferences.</p></div></div>@include('admin.nav')
<form class="panel-card settings-form" method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')@forelse($settings as $setting)<label>{{ str($setting->setting_key)->replace('_',' ')->title() }}<textarea name="settings[{{ $setting->setting_key }}]" rows="{{ str_contains($setting->setting_key, 'announcement') ? 4 : 2 }}">{{ $setting->setting_value }}</textarea></label>@empty<p>No settings have been created.</p>@endforelse<button class="button primary">Save settings</button></form>
@endsection
