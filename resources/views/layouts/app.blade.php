<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="app-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Prepza') }} — AI Decision Engine</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* ===== RESET ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ===== THEME VARS ===== */
        :root {
            --bg-base:    #0f1117;
            --bg-card:    #161b27;
            --bg-hover:   #1e2740;
            --border:     #2a3550;
            --text-main:  #e8eaf0;
            --text-muted: #8892a4;
            --text-dim:   #5a6478;
            --accent:     #6c63ff;
            --accent2:    #00d4aa;
            --sidebar-w:  220px;
        }
        .light-mode {
            --bg-base:    #f0f4fb;
            --bg-card:    #ffffff;
            --bg-hover:   #e8eeff;
            --border:     #dde3f0;
            --text-main:  #1a2135;
            --text-muted: #4a5568;
            --text-dim:   #94a3b8;
        }

        /* ===== BASE ===== */
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            transition: background 0.3s, color 0.3s;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 40;
            transition: background 0.3s, border-color 0.3s;
        }
        .sidebar-logo {
            padding: 20px 18px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px; color: #fff;
            flex-shrink: 0;
        }
        .logo-text { overflow: hidden; }
        .logo-name { font-size: 14px; font-weight: 800; white-space: nowrap; }
        .logo-sub  { font-size: 10px; color: var(--text-dim); white-space: nowrap; }

        /* Nav sections */
        .sidebar-nav { flex: 1; padding: 14px 10px; display: flex; flex-direction: column; gap: 2px; }
        .nav-section-label {
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text-dim);
            padding: 10px 8px 4px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: 8px;
            font-size: 13.5px; font-weight: 500; color: var(--text-muted);
            text-decoration: none; cursor: pointer; border: none; background: none;
            width: 100%; text-align: left;
            transition: background 0.15s, color 0.15s;
        }
        .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
        .nav-item.active {
            background: rgba(108, 99, 255, 0.12);
            color: var(--accent);
            font-weight: 600;
        }
        .nav-item.active .nav-icon { color: var(--accent); }
        .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 14px 10px;
            border-top: 1px solid var(--border);
        }
        .user-card {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 10px; border-radius: 8px;
            background: var(--bg-hover);
            margin-bottom: 8px;
        }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .user-name  { font-size: 13px; font-weight: 600; }
        .user-email { font-size: 11px; color: var(--text-dim); }
        .theme-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 6px 10px;
        }
        .theme-label { font-size: 12px; color: var(--text-muted); }
        .theme-toggle {
            display: flex; align-items: center; gap: 6px;
            background: var(--bg-hover); border: 1px solid var(--border);
            border-radius: 20px; padding: 4px 10px;
            cursor: pointer; font-size: 13px;
            transition: all 0.2s;
        }
        .theme-toggle:hover { border-color: var(--accent); }
        .logout-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 10px; border-radius: 8px; width: 100%;
            font-size: 13px; color: var(--text-dim); background: none; border: none;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: background 0.15s, color 0.15s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.1); color: #ef4444; }

        /* ===== MAIN AREA ===== */
        .main-wrap {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            position: sticky; top: 0; z-index: 30;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            transition: background 0.3s, border-color 0.3s;
        }
        .topbar-title { font-size: 16px; font-weight: 700; }
        .topbar-sub   { font-size: 11.5px; color: var(--text-dim); margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 14px; }
        .live-badge {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; color: var(--accent2);
        }
        .live-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--accent2);
            animation: pulse-anim 1.5s infinite;
        }
        @keyframes pulse-anim {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.5; transform: scale(1.3); }
        }
        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: var(--bg-base); border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 12px;
            font-size: 13px; color: var(--text-muted);
            transition: border-color 0.2s;
            cursor: text;
        }
        .search-box:focus-within { border-color: var(--accent); }
        .search-box input {
            background: none; border: none; outline: none;
            color: var(--text-main); font-size: 13px;
            font-family: 'Inter', sans-serif; width: 180px;
        }
        .search-box input::placeholder { color: var(--text-dim); }
        .topbar-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            cursor: pointer;
        }

        /* ===== PAGE CONTENT ===== */
        .page-content { flex: 1; overflow-y: auto; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            :root { --sidebar-w: 0px; }
            .sidebar { display: none; }
            .main-wrap { margin-left: 0; }
        }
    </style>
</head>
<body>

{{-- SIDEBAR --}}
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">P</div>
        <div class="logo-text">
            <div class="logo-name">Prepza</div>
            <div class="logo-sub">AI Decision Engine</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-label">Menu</span>

        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">🏠</span> Beranda
        </a>

        <a href="{{ route('intelligence.dashboard') }}"
           class="nav-item {{ request()->routeIs('intelligence.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">🧠</span> Intelligence
        </a>

        <span class="nav-section-label" style="margin-top:6px">Sistem</span>

        <a href="{{ route('profile.show') }}"
           class="nav-item {{ request()->routeIs('profile.show') ? 'active' : '' }}">
            <span class="nav-icon">⚙️</span> Pengaturan
        </a>

        @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
        <a href="{{ route('api-tokens.index') }}"
           class="nav-item {{ request()->routeIs('api-tokens.index') ? 'active' : '' }}">
            <span class="nav-icon">🔑</span> API Tokens
        </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
            <div style="overflow:hidden">
                <div class="user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ Auth::user()->name }}</div>
                <div class="user-email" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ Auth::user()->email }}</div>
            </div>
        </div>

        <div class="theme-row">
            <span class="theme-label">Tampilan</span>
            <button class="theme-toggle" onclick="toggleTheme()" id="themeBtn">🌙 Gelap</button>
        </div>

        <form method="POST" action="{{ route('logout') }}" x-data style="margin-top:4px">
            @csrf
            <button class="logout-btn" @click.prevent="$root.submit()">
                <span style="font-size:15px">↩</span> Keluar
            </button>
        </form>
    </div>
</aside>

{{-- MAIN AREA --}}
<div class="main-wrap">
    {{-- TOPBAR --}}
    <header class="topbar">
        <div>
            @if(isset($header))
                {{ $header }}
            @else
                <div class="topbar-title">Prepza</div>
                <div class="topbar-sub">AI-driven Decision Engine · Real-time</div>
            @endif
        </div>
        <div class="topbar-right">
            <div class="live-badge">
                <span class="live-dot"></span> Live
            </div>
            <div class="search-box">
                <span style="font-size:14px">🔍</span>
                <input type="text" placeholder="Cari antrian, insight...">
            </div>
            <div class="topbar-avatar" title="{{ Auth::user()->name }}">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    {{-- BANNER --}}
    <x-banner />

    {{-- CONTENT --}}
    <main class="page-content">
        {{ $slot }}
    </main>
</div>

@stack('modals')
@livewireScripts

<script>
    // Theme toggle
    const html = document.getElementById('app-html');
    const btn  = document.getElementById('themeBtn');
    const saved = localStorage.getItem('prepza_theme') || 'dark';
    applyTheme(saved);

    function applyTheme(t) {
        if (t === 'light') {
            html.classList.add('light-mode');
            if (btn) btn.textContent = '☀️ Terang';
        } else {
            html.classList.remove('light-mode');
            if (btn) btn.textContent = '🌙 Gelap';
        }
        localStorage.setItem('prepza_theme', t);
    }

    function toggleTheme() {
        const cur = localStorage.getItem('prepza_theme') || 'dark';
        applyTheme(cur === 'dark' ? 'light' : 'dark');
    }
</script>

</body>
</html>
