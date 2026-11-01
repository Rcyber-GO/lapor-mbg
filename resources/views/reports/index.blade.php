@extends('layouts.app')

@section('title', 'Laporan Saya')

@section('content')
<section class="panel">
    <div class="page-title-row">
        <h1 class="page-heading">Laporan Saya</h1>
        <a class="button" href="{{ route('reports.create') }}">+ Buat Laporan</a>
    </div>

    <form class="filter-form" method="GET" action="{{ route('reports.index') }}">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-small" type="submit">Terapkan</button>
        <a class="button button-secondary button-small" href="{{ route('reports.index') }}">Reset</a>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Dikirim</th>
                    <th>Sekolah</th>
                    <th>Distribusi</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>#{{ $report->id }}</td>
                        <td>{{ $report->created_at->translatedFormat('d M Y') }}</td>
                        <td>{{ $report->school->name }}</td>
                        <td>{{ $report->distribution->distribution_date->translatedFormat('d M Y') }}</td>
                        <td><span class="status-badge status-{{ $report->status }}">{{ $report->statusLabel() }}</span></td>
                        <td><a class="button button-secondary button-small" href="{{ route('reports.show', $report) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Belum ada laporan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</section>
@endsection
