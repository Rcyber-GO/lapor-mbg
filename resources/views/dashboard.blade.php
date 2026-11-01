@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<section class="panel">
    <h1 class="page-heading">Halo, {{ auth()->user()->name }}</h1>
    <p class="muted">Anda masuk sebagai <strong>{{ auth()->user()->roleLabel() }}</strong>.</p>

    @if (auth()->user()->isPelapor())
        <p>Di sini Anda akan dapat membuat laporan kejadian dan memantau perkembangan laporan milik Anda.</p>
    @elseif (auth()->user()->isPemantau())
        <p>Di sini Anda akan dapat meninjau kelengkapan laporan, melihat keterkaitan laporan, dan memberi catatan.</p>
    @elseif (auth()->user()->isKoordinator())
        <p>Di sini Anda akan dapat mengelola data sekolah dan distribusi, melihat rekap, dan mencatat penerusan informasi.</p>
    @endif
</section>
@endsection
