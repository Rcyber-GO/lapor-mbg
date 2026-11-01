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
        .button-secondary { background: #fff; color: #1f2937; border: 1px solid #d1d5db; }
        .button-danger { background: #b91c1c; }
        .button-small { padding: 6px 12px; font-size: .875rem; }
        .page-title-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 16px; }
        .filter-form label { display: block; font-size: .8rem; font-weight: 600; color: #4b5563; margin-bottom: 4px; }
        .filter-form input, .filter-form select { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        .form-actions { display: flex; gap: 10px; margin-top: 8px; }
        .table-wrap { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .data-table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; color: #4b5563; }
        .row-actions { display: flex; gap: 6px; }
        .row-actions form { margin: 0; }
        .empty-state { text-align: center; color: #6b7280; padding: 24px; }
        .pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin: 16px 0 0; flex-wrap: wrap; }
        .page-link { display: block; padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #1f2937; background: #fff; }
        .page-item.active .page-link { background: #15803d; border-color: #15803d; color: #fff; }
        .page-item.disabled .page-link { color: #9ca3af; }
        .status-badge { display: inline-block; border-radius: 999px; padding: 3px 12px; font-size: .85rem; font-weight: 600; background: #e5e7eb; color: #374151; }
        .status-dikirim { background: #dbeafe; color: #1e40af; }
        .status-ditinjau { background: #fef3c7; color: #92400e; }
        .status-perlu_dilengkapi { background: #ffedd5; color: #9a3412; }
        .status-perlu_diteruskan { background: #ede9fe; color: #5b21b6; }
        .status-diteruskan { background: #dcfce7; color: #166534; }
        .status-selesai { background: #e5e7eb; color: #374151; }
        .section-heading { font-size: 1.1rem; margin: 28px 0 12px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .note-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .note-item { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; background: #f9fafb; }
        .note-meta { font-size: .85rem; color: #6b7280; margin-bottom: 4px; }
        .action-panel { border: 1px solid #d1d5db; border-radius: 10px; padding: 16px; margin-top: 16px; }
        .action-panel h3 { margin: 0 0 10px; font-size: 1rem; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .detail-list { display: grid; grid-template-columns: 220px 1fr; gap: 10px 20px; margin: 20px 0 0; }
        .detail-list dt { font-weight: 600; color: #4b5563; }
        .detail-list dd { margin: 0; }
        .evidence-preview { max-width: 100%; max-height: 320px; border: 1px solid #e5e7eb; border-radius: 8px; }
        .alert { border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
        .alert ul { margin: 0; padding-left: 18px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        @media (max-width: 680px) {
            .nav { flex-wrap: wrap; gap: 12px; padding: 12px 16px; }
            .page { padding: 0 16px; margin-top: 20px; }
            .detail-list { grid-template-columns: 1fr; gap: 2px; }
            .detail-list dd { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
<header class="site-header">
    <nav class="nav">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}">Lapor MBG</a>
        @auth
            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
            @can('create-report')
                <a class="nav-link" href="{{ route('reports.index') }}">Laporan Saya</a>
            @endcan
            @can('viewAny', App\Models\Report::class)
                <a class="nav-link" href="{{ route('reports.incoming') }}">Laporan Masuk</a>
            @endcan
            @can('manage-master-data')
                <a class="nav-link" href="{{ route('schools.index') }}">Sekolah</a>
                <a class="nav-link" href="{{ route('distributions.index') }}">Distribusi</a>
            @endcan
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
@stack('scripts')
</body>
</html>
