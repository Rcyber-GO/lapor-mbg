@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<section class="panel auth-panel">
    <h1 class="page-heading">Masuk</h1>
    <p class="muted">Masuk untuk membuat dan memantau laporan keamanan makanan MBG.</p>
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="form-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <div class="form-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <label style="display: block; margin-bottom: 18px">
            <input type="checkbox" name="remember" value="1"> Ingat saya
        </label>
        <button class="button" type="submit">Masuk</button>
    </form>
    <p class="muted" style="margin-bottom: 0">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai pelapor</a>.</p>
</section>
@endsection
