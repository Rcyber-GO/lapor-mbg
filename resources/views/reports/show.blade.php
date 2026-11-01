@extends('layouts.app')

@section('title', 'Detail Laporan #'.$report->id)

@section('content')
<section class="panel" style="max-width: 820px">
    <div class="page-title-row">
        <h1 class="page-heading">Laporan #{{ $report->id }}</h1>
        <span class="status-badge status-{{ $report->status }}">{{ $report->statusLabel() }}</span>
    </div>
    <p class="muted">Dikirim {{ $report->created_at->translatedFormat('d M Y, H:i') }}</p>

    <dl class="detail-list">
        <dt>Sekolah</dt>
        <dd>{{ $report->school->name }}</dd>

        <dt>Distribusi</dt>
        <dd>{{ $report->distribution->distribution_date->translatedFormat('d M Y') }} &middot; {{ $report->distribution->menu }} ({{ $report->distribution->provider_name }})</dd>

        <dt>Hubungan dengan siswa</dt>
        <dd>{{ $report->relationLabel() }}</dd>

        <dt>Tanggal keluhan muncul</dt>
        <dd>{{ $report->incident_date->translatedFormat('d M Y') }}</dd>

        <dt>Perkiraan siswa terdampak</dt>
        <dd>{{ $report->affected_count }} orang</dd>

        <dt>Keluhan</dt>
        <dd>{{ $report->symptoms }}</dd>

        <dt>Kronologi</dt>
        <dd style="white-space: pre-line">{{ $report->description }}</dd>
    </dl>
</section>
@endsection
