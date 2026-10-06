@extends('layouts.app')
@section('title', 'System Settings')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-settings.css') }}?v=1">
<div class="admin-settings-page">
    <div class="admin-settings-heading">
        <h1><i class="fa-solid fa-gear"></i> System Settings</h1>
        <p>View the fixed configuration for this JTMK project system.</p>
    </div>

    <section class="system-configuration-card">
        <div class="system-configuration-heading">
            <span class="configuration-icon"><i class="fa-solid fa-sliders"></i></span>
            <div><h2>System Configuration</h2><p>These values are fixed to maintain the DFT50114 JTMK scope.</p></div>
        </div>

        <div class="configuration-grid">
            @foreach($configuration as $label => $value)
                <label class="configuration-field">
                    <span>{{ $label }}</span>
                    <input type="text" value="{{ $value }}" readonly aria-readonly="true">
                </label>
            @endforeach
        </div>

        <div class="configuration-note"><i class="fa-solid fa-circle-info"></i><span>Account, project and supervisor data are managed through the Admin pages. This system does not use numerical supervisor marks.</span></div>
    </section>
</div>
@endsection
