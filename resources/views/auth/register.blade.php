@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
<section class="panel auth-panel">
    <h1 class="page-heading">Daftar Akun Pelapor</h1>
    <p class="muted">Akun ini digunakan untuk membuat laporan dan memantau perkembangannya.</p>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="form-field">
            <label for="name">Nama</label>
            <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
        </div>
        <div class="form-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        </div>
        <div class="form-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
        </div>
        <div class="form-field">
            <label for="password_confirmation">Konfirmasi Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="button" type="submit">Daftar</button>
    </form>
    <p class="muted" style="margin-bottom: 0">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>.</p>
</section>
@endsection
