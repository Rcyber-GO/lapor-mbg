@extends('layouts.app')

@section('title', $school->exists ? 'Ubah Sekolah' : 'Tambah Sekolah')

@section('content')
<section class="panel" style="max-width: 640px">
    <h1 class="page-heading">{{ $school->exists ? 'Ubah Sekolah' : 'Tambah Sekolah' }}</h1>

    <form method="POST" action="{{ $school->exists ? route('schools.update', $school) : route('schools.store') }}">
        @csrf
        @if ($school->exists)
            @method('PUT')
        @endif

        <div class="form-field">
            <label for="name">Nama Sekolah</label>
            <input id="name" name="name" value="{{ old('name', $school->name) }}" maxlength="150" required>
        </div>
        <div class="form-field">
            <label for="level">Jenjang</label>
            <select id="level" name="level" required>
                <option value="">Pilih jenjang</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}" @selected(old('level', $school->level) === $level)>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label for="district">Kecamatan</label>
            <input id="district" name="district" value="{{ old('district', $school->district) }}" maxlength="100" required>
        </div>
        <div class="form-field">
            <label for="address">Alamat</label>
            <input id="address" name="address" value="{{ old('address', $school->address) }}" maxlength="255" required>
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Simpan</button>
            <a class="button button-secondary" href="{{ route('schools.index') }}">Batal</a>
        </div>
    </form>
</section>
@endsection
