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
        @can('viewAny', App\Models\Report::class)
            {{-- Hanya nama yang ditampilkan; kontak pribadi pelapor tidak diperlukan untuk peninjauan. --}}
            <dt>Pelapor</dt>
            <dd>{{ $report->user->name }}</dd>
        @endcan

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

        <dt>Bukti pendukung</dt>
        <dd>
            <a href="{{ route('reports.evidence', $report) }}" target="_blank" rel="noopener">{{ $report->evidence_name }}</a>
            @if ($report->evidenceIsImage())
                <a href="{{ route('reports.evidence', $report) }}" target="_blank" rel="noopener" style="display: block; margin-top: 10px">
                    <img src="{{ route('reports.evidence', $report) }}" alt="Bukti pendukung laporan #{{ $report->id }}" class="evidence-preview">
                </a>
            @endif
        </dd>
    </dl>

    <div class="form-actions" style="margin-top: 24px">
        @can('viewAny', App\Models\Report::class)
            <a class="button button-secondary" href="{{ route('reports.incoming') }}">&larr; Kembali ke Laporan Masuk</a>
        @elsecan('create-report')
            <a class="button button-secondary" href="{{ route('reports.index') }}">&larr; Kembali ke Laporan Saya</a>
        @endcan
    </div>
</section>
@endsection
