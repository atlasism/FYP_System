@extends('layouts.app')
@section('title', 'External Panel QR')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-panel-qr.css') }}?v=1">
<div class="admin-panel-qr-page">
    <div class="page-heading panel-qr-heading">
        <div><h1><i class="fa-solid fa-qrcode"></i> External Panel QR</h1><p>Generate a secure link for an external panel to evaluate Project Demonstration 3.</p></div>
        <span class="panel-qr-weight">Demo 3 | 15%</span>
    </div>

    @if($errors->any())<div class="notice error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="panel-qr-card panel-qr-generator">
            <h2>Generate Panel Access</h2>
            <p>Generate one QR for all groups or split groups across QR batches. Set the maximum number of panel members who can register with each QR.</p>
            @if($projects->isNotEmpty())
                <form method="post" action="{{ route('admin.panel-qr.generate') }}" class="shared-qr-form">
                    @csrf<input type="hidden" name="mode" value="shared">
                    <label for="shared-total-panels">Maximum panel members</label>
                    <input id="shared-total-panels" type="number" name="total_panels" min="2" max="255" value="{{ old('mode') === 'shared' ? old('total_panels', 4) : 4 }}" required>
                    <button class="button panel-qr-primary" type="submit"><i class="fa-solid fa-qrcode"></i> Generate QR</button>
                </form>

                <details class="split-qr-details" @if(old('mode') === 'split' && $errors->any()) open @endif>
                    <summary>Set up split QR batches</summary>
                    <form method="post" action="{{ route('admin.panel-qr.generate') }}" id="split-qr-form">
                        @csrf<input type="hidden" name="mode" value="split">
                        <div class="split-qr-fields">
                            <label>Total panel members<input id="split-total-panels" type="number" name="total_panels" min="2" max="255" value="{{ old('mode') === 'split' ? old('total_panels', 6) : 6 }}" required></label>
                            <label>Number of QR batches<input id="split-qr-count" type="number" name="qr_count" min="1" max="{{ $projects->count() }}" value="{{ old('qr_count', min(2, $projects->count())) }}" required></label>
                        </div>
                        <p class="split-help" id="qr-count-hint">Up to {{ $projects->count() }} QR batches. Each QR needs at least 2 panel members; a panel member can use more than one QR.</p>
                        <div class="qr-batch-list" id="qr-batch-list" data-groups="{{ $projects->pluck('group_no')->implode(',') }}" data-saved-groups="{{ implode(',', old('group_sizes', [])) }}" data-saved-panels="{{ implode(',', old('panel_counts', [])) }}"></div>
                        <div class="qr-batch-summary"><span id="group-count-summary"></span><span id="panel-count-summary"></span></div>
                        <p class="split-help">Set any group split you need. Each group is assigned to one QR batch. Panel members can be assigned to multiple batches; each QR needs at least 2.</p>
                        <button class="button panel-qr-primary" type="submit"><i class="fa-solid fa-qrcode"></i> Generate Split QRs</button>
                    </form>
                </details>
            @else
                <div class="panel-qr-empty-note">No current DFT50114 groups are available.</div>
            @endif
    </section>

    @if($generatedQrs)
        <section class="generated-qr-section">
            <div class="generated-qr-heading"><div><span class="eyebrow">PANEL ACCESS</span><h2>{{ count($generatedQrs) === 1 ? 'Generated QR Code' : 'Generated QR Batches' }}</h2></div><span>Valid for 7 days</span></div>
            <div class="generated-qr-grid">
                @foreach($generatedQrs as $index => $qr)
                    <article class="generated-qr-card">
                        <h3>{{ count($generatedQrs) === 1 ? 'QR Code' : 'QR Batch '.($index + 1) }}</h3>
                        <p>{{ $qr['panel_count'] }} panel members · {{ $qr['group_count'] }} groups</p>
                        <small>Groups {{ implode(', ', $qr['groups']) }}</small>
                        <div class="qr-canvas-wrap"><canvas data-qr-url="{{ $qr['url'] }}" aria-label="QR code for panel batch {{ $index + 1 }}"></canvas></div>
                        <div class="panel-qr-link-row"><input type="text" readonly value="{{ $qr['url'] }}" aria-label="Panel evaluation link"><button class="button panel-qr-copy" type="button" data-copy-link="{{ $qr['url'] }}" title="Copy link"><i class="fa-regular fa-copy"></i></button></div>
                        <span class="panel-qr-copy-status" aria-live="polite"></span>
                        <p class="panel-qr-share-hint">Share this QR with the panel members. Each panel enters their name and submits separate scores.</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@vite('resources/js/admin-panel-qr.js')
@endsection
