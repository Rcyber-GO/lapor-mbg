@extends('layouts.app')

@section('title', 'Buat Laporan')

@section('content')
<section class="panel" style="max-width: 720px">
    <h1 class="page-heading">Buat Laporan Kejadian</h1>
    <p class="muted">Laporkan keluhan kesehatan yang muncul setelah konsumsi makanan MBG. Laporan akan ditinjau kelengkapannya oleh Pemantau Komunitas.</p>

    <form method="POST" action="{{ route('reports.store') }}">
        @csrf

        <div class="form-field">
            <label for="school_id">Sekolah</label>
            <select id="school_id" name="school_id" required>
                <option value="">Pilih sekolah</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id') === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label for="distribution_id">Distribusi Makanan</label>
            {{-- Opsi diisi report-form.js lewat fetch() setelah sekolah dipilih. --}}
            <select id="distribution_id" name="distribution_id" required disabled
                    data-url-template="{{ route('schools.distributions', ['school' => '__SCHOOL__']) }}"
                    data-selected="{{ old('distribution_id') }}">
                <option value="">Pilih sekolah terlebih dahulu</option>
            </select>
        </div>
        <div class="form-field">
            <label for="reporter_relation">Hubungan Anda dengan siswa</label>
            <select id="reporter_relation" name="reporter_relation" required>
                <option value="">Pilih</option>
                @foreach ($relations as $value => $label)
                    <option value="{{ $value }}" @selected(old('reporter_relation') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label for="incident_date">Tanggal keluhan muncul</label>
            <input id="incident_date" name="incident_date" type="date" value="{{ old('incident_date') }}" max="{{ now()->toDateString() }}" required>
        </div>
        <div class="form-field">
            <label for="affected_count">Perkiraan jumlah siswa yang mengalami keluhan</label>
            <input id="affected_count" name="affected_count" type="number" min="1" max="5000" value="{{ old('affected_count', 1) }}" required>
        </div>
        <div class="form-field">
            <label for="symptoms">Keluhan yang dialami</label>
            <input id="symptoms" name="symptoms" value="{{ old('symptoms') }}" maxlength="255" placeholder="mis. mual, muntah, sakit perut" required>
        </div>
        <div class="form-field">
            <label for="description">Kronologi kejadian</label>
            <textarea id="description" name="description" rows="5" minlength="20" maxlength="3000" placeholder="Ceritakan kapan makanan dikonsumsi, kapan keluhan muncul, dan informasi lain yang Anda ketahui." required>{{ old('description') }}</textarea>
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Kirim Laporan</button>
            <a class="button button-secondary" href="{{ route('dashboard') }}">Batal</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/report-form.js') }}" defer></script>
@endpush
