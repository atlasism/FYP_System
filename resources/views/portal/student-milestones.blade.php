@extends('layouts.app')
@section('title', 'Milestone Verification Status')
@section('content')
<div class="legacy-heading"><div><h1><i class="fa-solid fa-flag-checkered"></i> Milestone Verification Status</h1><p>Semak status pengesahan Demo 1 dan Demo 2 bagi projek anda.</p></div></div>
<section class="panel-card"><h2><i class="fa-solid fa-list-check"></i> Demo Milestone Status</h2><div class="table-scroll"><table><thead><tr><th>Milestone</th><th>Status</th></tr></thead><tbody>@foreach(['Demo 1', 'Demo 2'] as $demo)<tr><td><strong>{{ $demo }}</strong></td><td><span class="status-pill">{{ $verification[$demo] ?? 'Pending' }}</span></td></tr>@endforeach</tbody></table></div></section>
@endsection
