@extends('layouts.app')

@section('title', 'Laporan Masuk')

@section('content')
<section class="panel">
    <div class="page-title-row">
        <h1 class="page-heading">Laporan Masuk</h1>
        <span class="muted">{{ $reports->total() }} laporan</span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Dikirim</th>
                    <th>Pelapor</th>
                    <th>Sekolah</th>
                    <th>Distribusi</th>
                    <th>Terdampak</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>#{{ $report->id }}</td>
                        <td>{{ $report->created_at->translatedFormat('d M Y') }}</td>
                        <td>{{ $report->user->name }}</td>
                        <td>{{ $report->school->name }}</td>
                        <td>{{ $report->distribution->distribution_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $report->affected_count }} orang</td>
                        <td><span class="status-badge status-{{ $report->status }}">{{ $report->statusLabel() }}</span></td>
                        <td><a class="button button-secondary button-small" href="{{ route('reports.show', $report) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">Belum ada laporan masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</section>
@endsection
