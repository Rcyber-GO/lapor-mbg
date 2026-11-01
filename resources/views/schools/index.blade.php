@extends('layouts.app')

@section('title', 'Data Sekolah')

@section('content')
<section class="panel">
    <div class="page-title-row">
        <h1 class="page-heading">Data Sekolah</h1>
        <a class="button" href="{{ route('schools.create') }}">+ Tambah Sekolah</a>
    </div>

    <form class="filter-form" method="GET" action="{{ route('schools.index') }}">
        <div>
            <label for="q">Cari nama</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="mis. Harapan">
        </div>
        <div>
            <label for="level">Jenjang</label>
            <select id="level" name="level">
                <option value="">Semua</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-small" type="submit">Terapkan</button>
        <a class="button button-secondary button-small" href="{{ route('schools.index') }}">Reset</a>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Sekolah</th>
                    <th>Jenjang</th>
                    <th>Kecamatan</th>
                    <th>Alamat</th>
                    <th>Distribusi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schools as $school)
                    <tr>
                        <td>{{ $school->name }}</td>
                        <td>{{ $school->level }}</td>
                        <td>{{ $school->district }}</td>
                        <td>{{ $school->address }}</td>
                        <td>{{ $school->distributions_count }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="button button-secondary button-small" href="{{ route('schools.edit', $school) }}">Ubah</a>
                                <form method="POST" action="{{ route('schools.destroy', $school) }}" onsubmit="return confirm('Hapus sekolah {{ $school->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button-danger button-small" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Tidak ada sekolah yang sesuai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $schools->links() }}
</section>
@endsection
