@extends('layouts.app')

@section('title', 'Data Distribusi')

@section('content')
<section class="panel">
    <div class="page-title-row">
        <h1 class="page-heading">Data Distribusi Makanan</h1>
        <a class="button" href="{{ route('distributions.create') }}">+ Tambah Distribusi</a>
    </div>

    <form class="filter-form" method="GET" action="{{ route('distributions.index') }}">
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
            <label for="date_from">Dari tanggal</label>
            <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
        </div>
        <div>
            <label for="date_to">Sampai tanggal</label>
            <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
        </div>
        <button class="button button-small" type="submit">Terapkan</button>
        <a class="button button-secondary button-small" href="{{ route('distributions.index') }}">Reset</a>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Sekolah</th>
                    <th>Menu</th>
                    <th>SPPG</th>
                    <th>Porsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($distributions as $distribution)
                    <tr>
                        <td>{{ $distribution->distribution_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $distribution->school->name }}</td>
                        <td>{{ $distribution->menu }}</td>
                        <td>{{ $distribution->provider_name }}</td>
                        <td>{{ $distribution->portion_count ?? '-' }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="button button-secondary button-small" href="{{ route('distributions.edit', $distribution) }}">Ubah</a>
                                <form method="POST" action="{{ route('distributions.destroy', $distribution) }}" onsubmit="return confirm('Hapus distribusi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button-danger button-small" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Tidak ada distribusi yang sesuai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $distributions->links() }}
</section>
@endsection
