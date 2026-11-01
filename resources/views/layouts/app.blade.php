<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') · Lapor MBG</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f4f6f9; color: #1f2937; line-height: 1.5; }
        a { color: #1d4ed8; }
        .site-header { background: #14532d; color: #fff; }
        .nav { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; gap: 20px; padding: 14px 24px; }
        .brand { color: #fff; font-weight: 800; font-size: 1.15rem; text-decoration: none; margin-right: auto; }
        .nav-link { color: #dcfce7; text-decoration: none; font-weight: 600; }
        .role-badge { background: rgba(255,255,255,.15); border-radius: 999px; padding: 4px 12px; font-size: .85rem; }
        .logout-button { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.5); border-radius: 8px; padding: 6px 12px; font: inherit; cursor: pointer; }
        .page { max-width: 1100px; margin: 32px auto; padding: 0 24px; }
        .panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
        .auth-panel { max-width: 420px; margin: 0 auto; }
        .page-heading { margin: 0 0 8px; font-size: 1.5rem; }
        .muted { color: #6b7280; }
        .form-field { margin-bottom: 16px; }
        .form-field label { display: block; font-weight: 600; margin-bottom: 6px; }
        .form-field input, .form-field select, .form-field textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        .button { display: inline-block; background: #15803d; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .alert { border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
        .alert ul { margin: 0; padding-left: 18px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        @media (max-width: 680px) {
            .nav { flex-wrap: wrap; gap: 12px; padding: 12px 16px; }
            .page { padding: 0 16px; margin-top: 20px; }
        }
    </style>
</head>
<body>
<header class="site-header">
    <nav class="nav">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}">Lapor MBG</a>
        @auth
            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="role-badge" title="Peran akun yang sedang login">{{ auth()->user()->name }} &middot; {{ auth()->user()->roleLabel() }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0">
                @csrf
                <button class="logout-button" type="submit">Keluar</button>
            </form>
        @else
            <a class="nav-link" href="{{ route('login') }}">Masuk</a>
            <a class="nav-link" href="{{ route('register') }}">Daftar</a>
        @endauth
    </nav>
</header>
<main class="page">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
