@extends('layouts.app')

@section('title', 'Ubah Laporan #'.$report->id)

@section('content')
<section class="panel" style="max-width: 720px">
    <h1 class="page-heading">Ubah Laporan #{{ $report->id }}</h1>
    @if ($report->status === App\Models\Report::STATUS_PERLU_DILENGKAPI)
        <p class="muted">Lengkapi laporan sesuai catatan Pemantau Komunitas. Setelah disimpan, laporan dikirim ulang untuk ditinjau.</p>
    @else
        <p class="muted">Laporan masih dapat diubah selama belum mulai ditinjau.</p>
    @endif

    <form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('reports._fields')

        <div class="form-actions">
            <button class="button" type="submit">Simpan Perubahan</button>
            <a class="button button-secondary" href="{{ route('reports.show', $report) }}">Batal</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/report-form.js') }}" defer></script>
@endpush
