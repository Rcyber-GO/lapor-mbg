@extends('layouts.app')

@section('title', 'Rekap Laporan')

@section('content')
<section class="panel">
    <div class="page-title-row">
        <h1 class="page-heading">Rekap Laporan</h1>
        <span class="muted">Berdasarkan tanggal kejadian</span>
    </div>

    <form class="filter-form" method="GET" action="{{ route('recap') }}">
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
        <a class="button button-secondary button-small" href="{{ route('recap') }}">Reset</a>
    </form>

    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-label">Total laporan</span>
            <span class="stat-value" data-stat="total">{{ $summary['total'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Masih diproses</span>
            <span class="stat-value" data-stat="in_progress">{{ $summary['in_progress'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Menunggu diteruskan</span>
            <span class="stat-value" data-stat="to_forward">{{ $summary['to_forward'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Sudah diteruskan</span>
            <span class="stat-value" data-stat="forwarded">{{ $summary['forwarded'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Perkiraan siswa terdampak</span>
            <span class="stat-value" data-stat="affected">{{ $summary['affected'] }}</span>
        </div>
    </div>

    <h2 class="section-heading">Rekap per Sekolah</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sekolah</th>
                    <th>Total</th>
                    <th>Masih diproses</th>
                    <th>Menunggu diteruskan</th>
                    <th>Sudah diteruskan</th>
                    <th>Selesai dipantau</th>
                    <th>Perkiraan terdampak</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->school_name }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ $row->in_progress }}</td>
                        <td>{{ $row->to_forward }}</td>
                        <td>{{ $row->forwarded }}</td>
                        <td>{{ $row->finished }}</td>
                        <td>{{ $row->affected }} orang</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">Tidak ada laporan pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="muted" style="font-size: .85rem">"Masih diproses" mencakup laporan berstatus Dikirim, Sedang Ditinjau, Perlu Dilengkapi, dan Ditandai untuk Diteruskan. Angka terdampak adalah perkiraan dari pelapor, bukan data resmi.</p>
</section>
@endsection
