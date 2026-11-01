@extends('layouts.app')

@section('title', 'Buat Laporan')

@section('content')
<section class="panel" style="max-width: 720px">
    <h1 class="page-heading">Buat Laporan Kejadian</h1>
    <p class="muted">Laporkan keluhan kesehatan yang muncul setelah konsumsi makanan MBG. Laporan akan ditinjau kelengkapannya oleh Pemantau Komunitas.</p>

    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
        @csrf

        @include('reports._fields', ['report' => new App\Models\Report])

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
