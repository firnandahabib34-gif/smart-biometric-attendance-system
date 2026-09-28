<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Smart Biometric</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body" x-data="{ sidebarOpen: false }">
    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="23" height="23" aria-hidden="true"><path stroke-linecap="round" d="M7 8.5a5 5 0 0 1 10 0c0 4.6-.8 8.1-2.4 11M4.6 10.5c0 3.4-.4 6-1.2 7.8M19.4 10.5c0 1.8-.1 3.4-.4 4.8M8.5 11c-.1 3.4-.5 5.7-1.4 7.5m5-6.5c0 2.6-.4 4.8-1.3 6.6"/></svg></span>
            <span><span class="brand-name">Smart Biometric</span><span class="brand-sub">Attendance system</span></span>
        </a>
        <div class="nav-label">Workspace</div>
        <nav class="nav-list" aria-label="Navigasi utama">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>Dashboard</a>
            <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="8" r="3.3"/><path stroke-linecap="round" d="M3.5 19.5v-1.2a5.5 5.5 0 0 1 11 0v1.2m2-9.2a3.3 3.3 0 0 0 0-6.4m1 15.6v-1.2a5.5 5.5 0 0 0-3.2-5"/></svg>Mahasiswa</a>
            <a class="nav-link {{ request()->routeIs('fingerprint.*') ? 'active' : '' }}" href="{{ route('fingerprint.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" d="M7 8.5a5 5 0 0 1 10 0c0 4.6-.8 8.1-2.4 11M4.6 10.5c0 3.4-.4 6-1.2 7.8M19.4 10.5c0 1.8-.1 3.4-.4 4.8M8.5 11c-.1 3.4-.5 5.7-1.4 7.5m5-6.5c0 2.6-.4 4.8-1.3 6.6"/></svg>Fingerprint</a>
            <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path stroke-linecap="round" d="M7.5 3.5v3m9-3v3M3.5 9h17m-12 4h3m-3 3h7"/></svg>Absensi &amp; laporan</a>
            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="m19.4 15 .1.1a1.7 1.7 0 1 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 1 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 1 1-2.4-2.4l.1-.1A1.7 1.7 0 0 0 4.2 12H4a1.7 1.7 0 1 1 0-3.4h.2A1.7 1.7 0 0 0 5.4 5.7l-.1-.1a1.7 1.7 0 1 1 2.4-2.4l.1.1A1.7 1.7 0 0 0 10.7 2h.2a1.7 1.7 0 1 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 1 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 1 1 0 3.4h-.2a1.7 1.7 0 0 0-1.5 2.9Z"/></svg>Pengaturan</a>
        </nav>
        <div class="sidebar-bottom"><div class="sidebar-rule"></div><div class="sidebar-user">
            <span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <span style="min-width:0"><span class="user-name">{{ auth()->user()->name }}</span><span class="user-role">Administrator</span></span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit" title="Keluar" aria-label="Keluar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></button></form>
        </div></div>
    </aside>
    <div class="shell-main">
        <header class="topbar"><div class="topbar-left">
            <button class="mobile-menu" type="button" @click="sidebarOpen = !sidebarOpen" aria-label="Buka navigasi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <div><div class="top-title">@yield('title', 'Smart Biometric')</div><div class="top-date">{{ now()->translatedFormat('l, d F Y') }}</div></div>
        </div><div class="top-meta"><span class="live-chip"><span class="live-dot"></span>Sistem aktif</span></div></header>
        <main class="page-content">
            @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="notice notice-error">Periksa kembali data yang dimasukkan.</div>@endif
            @yield('content')
        </main>
    </div>
</body>
</html>