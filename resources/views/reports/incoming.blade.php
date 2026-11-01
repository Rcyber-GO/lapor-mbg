@extends('layouts.app')

@section('title', 'Laporan Masuk')

@section('content')
<section class="panel">
    <div class="page-title-row">
        <h1 class="page-heading">Laporan Masuk</h1>
        <span class="muted">{{ $reports->total() }} laporan</span>
    </div>

    <form class="filter-form" method="GET" action="{{ route('reports.incoming') }}">
        <div>
            <label for="q">Kata kunci</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="No., keluhan, kronologi, sekolah">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="school_id">Sekolah</label>
            <select id="school_id" name="school_id">
                <option value="">Semua sekolah</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) request('school_id') === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from">Kejadian dari</label>
            <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
        </div>
        <div>
            <label for="date_to">Sampai</label>
            <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
        </div>
        <button class="button button-small" type="submit">Terapkan</button>
        <a class="button button-secondary button-small" href="{{ route('reports.incoming') }}">Reset</a>
    </form>

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
                    <tr><td colspan="8" class="empty-state">{{ request()->hasAny(['q', 'status', 'school_id', 'date_from', 'date_to']) ? 'Tidak ada laporan yang sesuai dengan filter.' : 'Belum ada laporan masuk.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</section>
@endsection
