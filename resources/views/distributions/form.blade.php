@extends('layouts.app')

@section('title', $distribution->exists ? 'Ubah Distribusi' : 'Tambah Distribusi')

@section('content')
<section class="panel" style="max-width: 640px">
    <h1 class="page-heading">{{ $distribution->exists ? 'Ubah Distribusi' : 'Tambah Distribusi' }}</h1>

    <form method="POST" action="{{ $distribution->exists ? route('distributions.update', $distribution) : route('distributions.store') }}">
        @csrf
        @if ($distribution->exists)
            @method('PUT')
        @endif

        <div class="form-field">
            <label for="school_id">Sekolah</label>
            <select id="school_id" name="school_id" required>
                <option value="">Pilih sekolah</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id', $distribution->school_id) === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label for="distribution_date">Tanggal Distribusi</label>
            <input id="distribution_date" name="distribution_date" type="date" value="{{ old('distribution_date', $distribution->distribution_date?->toDateString()) }}" required>
        </div>
        <div class="form-field">
            <label for="menu">Menu</label>
            <input id="menu" name="menu" value="{{ old('menu', $distribution->menu) }}" maxlength="255" placeholder="mis. Nasi, ayam goreng, sayur bayam, pisang" required>
        </div>
        <div class="form-field">
            <label for="provider_name">Nama SPPG Penyedia</label>
            <input id="provider_name" name="provider_name" value="{{ old('provider_name', $distribution->provider_name) }}" maxlength="150" required>
        </div>
        <div class="form-field">
            <label for="portion_count">Jumlah Porsi (opsional)</label>
            <input id="portion_count" name="portion_count" type="number" min="1" value="{{ old('portion_count', $distribution->portion_count) }}">
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Simpan</button>
            <a class="button button-secondary" href="{{ route('distributions.index') }}">Batal</a>
        </div>
    </form>
</section>
@endsection
