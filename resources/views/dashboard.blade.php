@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<section class="panel">
    <h1 class="page-heading">Halo, {{ auth()->user()->name }}</h1>
    <p class="muted">Anda masuk sebagai <strong>{{ auth()->user()->roleLabel() }}</strong>.</p>

    @if (auth()->user()->isPelapor())
        <p>Laporkan keluhan kesehatan setelah konsumsi makanan MBG dan pantau perkembangan laporan Anda.</p>
        <a class="button" href="{{ route('reports.create') }}">+ Buat Laporan</a>
    @elseif (auth()->user()->isPemantau())
        <p>Tinjau kelengkapan laporan yang masuk dan keterkaitannya dengan sekolah atau distribusi yang sama.</p>
        <a class="button" href="{{ route('reports.incoming') }}">Lihat Laporan Masuk</a>
    @elseif (auth()->user()->isKoordinator())
        <p>Kelola data sekolah dan distribusi, pantau laporan masuk, dan catat penerusan informasi kepada pihak terkait.</p>
        <a class="button" href="{{ route('reports.incoming') }}">Lihat Laporan Masuk</a>
    @endif
</section>
@endsection
