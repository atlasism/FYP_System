@extends('layouts.app')
@section('title', 'Milestone Verification Status')
@section('content')
<div class="legacy-heading"><div><h1><i class="fa-solid fa-flag-checkered"></i> Milestone Verification Status</h1><p>Semak status pengesahan Demo 1 dan Demo 2 bagi projek anda.</p></div></div>
<section class="panel-card milestone-card"><h2><i class="fa-solid fa-list-check"></i> Demo Milestone Status</h2><div class="table-scroll"><table class="milestone-table"><thead><tr><th>Milestone</th><th>Status</th></tr></thead><tbody>@foreach(['Demo 1', 'Demo 2'] as $demo)@php($status = $verification[$demo] ?? 'Pending')<tr><td><i class="fa-solid fa-person-chalkboard milestone-icon"></i><strong>{{ $demo }}</strong></td><td><span class="milestone-status {{ $status === 'Passed' ? 'passed' : ($status === 'Not Passed' ? 'not-passed' : 'pending') }}">{{ $status }}</span></td></tr>@endforeach</tbody></table></div></section>
@endsection
