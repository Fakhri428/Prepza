<x-app-layout>
    <x-slot name="header">
        <div style="display:flex;flex-direction:column">
            <div style="font-size:17px;font-weight:800;color:var(--text-main)" class="topbar-title">Beranda — Prepza</div>
            <div style="font-size:11.5px;color:var(--text-dim)" class="topbar-sub">AI Decision Engine · Overview Sistem</div>
        </div>
    </x-slot>

    <style>
        .db-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .db-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .db-grid-32 { display:grid; grid-template-columns:2fr 1fr; gap:16px; }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            transition: box-shadow .2s, transform .2s;
        }
        .card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.15); transform:translateY(-2px); }
        .card-pad { padding: 18px 20px; }
        .card-hdr {
            padding: 16px 20px 14px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-size:14px; font-weight:700; }
        .card-sub { font-size:11.5px; color:var(--text-dim); margin-top:2px; }
        /* STAT CARDS */
        .stat-num { font-size:26px; font-weight:800; margin:6px 0 4px; }
        .stat-label { font-size:12px; color:var(--text-dim); }
        .stat-change { font-size:11.5px; font-weight:600; }
        .change-up   { color:#10b981; }
        .change-down { color:#ef4444; }
        .stat-icon {
            width:40px; height:40px; border-radius:10px;
            display:flex; align-items:center; justify-content:center; font-size:18px;
        }
        /* BARS */
        .bar-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .bar-label { font-size:12.5px; color:var(--text-muted); min-width:180px; }
        .bar-track  { flex:1; background:var(--border); border-radius:4px; height:6px; }
        .bar-fill   { border-radius:4px; height:100%; background:linear-gradient(90deg,var(--accent),var(--accent2)); }
        .bar-pct    { font-size:12px; font-weight:600; color:var(--accent); min-width:34px; text-align:right; }
        /* STEPS */
        .step-row { display:flex; gap:12px; align-items:flex-start; padding: 10px 0; border-bottom:1px solid var(--border); }
        .step-row:last-child { border-bottom:none; }
        .step-num-b {
            width:28px; height:28px; border-radius:50%;
            background:linear-gradient(135deg,var(--accent),var(--accent2));
            color:#fff; display:flex; align-items:center; justify-content:center;
            font-size:12px; font-weight:800; flex-shrink:0;
        }
        .step-t { font-size:13px; font-weight:700; color:var(--text-main); margin-bottom:3px; }
        .step-d { font-size:12px; color:var(--text-dim); line-height:1.5; }
        /* DONUT CHART */
        .donut-wrap { display:flex; align-items:center; gap:20px; padding:16px 20px; }
        .donut-svg { flex-shrink:0; }
        .legend-item { display:flex; align-items:center; gap:7px; font-size:12.5px; margin-bottom:6px; }
        .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
        /* MINI CHART (CSS bar-based) */
        .chart-bars { display:flex; align-items:flex-end; gap:5px; height:80px; padding:0; }
        .chart-bar {
            flex:1; border-radius:4px 4px 0 0;
            background: linear-gradient(180deg, var(--accent) 0%, rgba(108,99,255,0.3) 100%);
            transition: opacity .2s;
            cursor: pointer;
        }
        .chart-bar:hover { opacity:0.8; }
        .chart-labels { display:flex; gap:5px; margin-top:4px; }
        .chart-lbl { flex:1; text-align:center; font-size:9px; color:var(--text-dim); }
    </style>

    <div style="padding:24px; display:flex; flex-direction:column; gap:18px">

        {{-- HERO BANNER --}}
        <div style="background:linear-gradient(135deg,rgba(108,99,255,0.1),rgba(0,212,170,0.06));border:1px solid var(--border);border-radius:14px;padding:24px 28px;display:flex;align-items:center;justify-content:space-between;position:relative;overflow:hidden">
            <div style="position:absolute;right:-50px;top:-50px;width:200px;height:200px;background:radial-gradient(circle,rgba(108,99,255,0.1),transparent 70%);border-radius:50%"></div>
            <div style="position:relative;z-index:1">
                <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(108,99,255,0.1);border:1px solid rgba(108,99,255,0.2);border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:600;color:var(--accent);margin-bottom:10px">
                    <span style="width:6px;height:6px;border-radius:50%;background:#10b981;animation:pulse-anim 1.5s infinite;display:inline-block"></span>
                    AI Engine Aktif
                </div>
                <h1 style="font-size:20px;font-weight:800;margin-bottom:6px;color:var(--text-main)">Selamat datang, {{ Auth::user()->name }} 👋</h1>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.6;max-width:480px">Platform AI-driven untuk mengolah antrian secara real-time. Gunakan Intelligence Dashboard untuk analisis mendalam.</p>
                <a href="{{ route('intelligence.dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--accent),#5b52e8);color:#fff;padding:9px 18px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;margin-top:14px;box-shadow:0 4px 14px rgba(108,99,255,0.3)">
                    🧠 Buka Intelligence Dashboard →
                </a>
            </div>
            <div style="display:flex;gap:12px;flex-shrink:0">
                @foreach([
                    [number_format((int) ($stats['total_orders'] ?? 0)).' aktif','Order Aktif','#6c63ff'],
                    [number_format((int) ($stats['processing_orders'] ?? 0)),'Processing','#10b981'],
                    [number_format((int) ($stats['completed_today'] ?? 0)),'Done Hari Ini','#f59e0b']
                ] as [$v,$l,$c])
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:14px 18px;text-align:center;min-width:80px">
                    <div style="font-size:20px;font-weight:800;color:{{$c}}">{{$v}}</div>
                    <div style="font-size:10.5px;color:var(--text-dim);margin-top:2px">{{$l}}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- STAT CARDS --}}
        <div class="db-grid-4">
            @php
                $dailyRevenue = 'Rp '.number_format((float) ($stats['daily_revenue'] ?? 0), 0, ',', '.');
                $monthlyRevenue = 'Rp '.number_format((float) ($stats['monthly_revenue'] ?? 0), 0, ',', '.');
            @endphp
            @foreach([
                ['Total Antrian Aktif', number_format((int) ($stats['total_orders'] ?? 0)), '🗂️', 'rgba(108,99,255,0.12)'],
                ['Sedang Diproses', number_format((int) ($stats['processing_orders'] ?? 0)), '⚙️', 'rgba(0,212,170,0.1)'],
                ['Priority High', number_format((int) ($stats['high_priority'] ?? 0)), '⚠️', 'rgba(239,68,68,0.1)'],
                ['Selesai Hari Ini', number_format((int) ($stats['completed_today'] ?? 0)), '✅', 'rgba(16,185,129,0.1)'],
                ['Total Pemasukan Harian', $dailyRevenue, '💰', 'rgba(16,185,129,0.12)'],
                ['Total Pemasukan Bulanan', $monthlyRevenue, '🏦', 'rgba(108,99,255,0.12)'],
                ['Total Order Hari Ini', number_format((int) ($stats['orders_today'] ?? 0)), '📅', 'rgba(245,158,11,0.12)'],
                ['Total Order Bulan Ini', number_format((int) ($stats['orders_month'] ?? 0)), '🗓️', 'rgba(0,212,170,0.12)'],
            ] as [$label,$val,$icon,$ibg])
            <div class="card card-pad">
                <div style="display:flex;align-items:flex-start;justify-content:space-between">
                    <div>
                        <div class="stat-label">{{$label}}</div>
                        <div class="stat-num" style="color:var(--text-main)">{{$val}}</div>
                        <div class="stat-change" style="color:var(--text-dim)">Data real-time dari database lokal</div>
                    </div>
                    <div class="stat-icon" style="background:{{$ibg}}">{{$icon}}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- CHARTS ROW --}}
        <div class="db-grid-32">
            {{-- Bar Chart --}}
            <div class="card">
                <div class="card-hdr">
                    <div><div class="card-title">Throughput Antrian (24 Jam)</div><div class="card-sub">Volume order per jam · Hari ini</div></div>
                </div>
                <div style="padding:16px 20px">
                    <div class="chart-bars">
                        @foreach($throughputChart as $point)
                        <div class="chart-bar" style="height:{{ max(4, (int) $point['height']) }}%" title="{{ $point['label'] }}:00 · {{ $point['count'] }} order"></div>
                        @endforeach
                    </div>
                    <div class="chart-labels">
                        @foreach($throughputChart as $point)
                        <span class="chart-lbl" style="display:{{($loop->index % 3 === 0)?'block':'none'}}">{{ $point['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Donut Chart --}}
            <div class="card">
                <div class="card-hdr">
                    <div><div class="card-title">Distribusi Status Order</div><div class="card-sub">Berdasarkan data status pada database</div></div>
                </div>
                @php
                    $totalStatusOrders = collect($statusDistribution)->sum('count');
                @endphp
                <div class="donut-wrap">
                    <div class="donut-svg" style="width:100px;height:100px;border-radius:50%;border:10px solid var(--border);display:flex;align-items:center;justify-content:center;flex-direction:column">
                        <div style="font-size:20px;font-weight:800;color:var(--text-main)">{{ $totalStatusOrders }}</div>
                        <div style="font-size:10px;color:var(--text-dim)">Total</div>
                    </div>
                    <div>
                        @foreach($statusDistribution as $status)
                        <div class="legend-item">
                            <div class="legend-dot" style="background:{{ $status['color'] }}"></div>
                            <span style="color:var(--text-muted)">{{ $status['label'] }}</span>
                            <span style="font-weight:600;color:var(--text-main);margin-left:auto">{{ $status['count'] }} ({{ $status['percentage'] }}%)</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTTOM ROW --}}
        <div class="db-grid-2">
            {{-- AI Capabilities --}}
            <div class="card">
                <div class="card-hdr">
                    <div><div class="card-title">Kinerja Sistem Berdasarkan Data</div><div class="card-sub">Ringkasan metrik operasional</div></div>
                </div>
                <div class="card-pad">
                    @foreach($engineMetrics as $metric)
                    <div class="bar-row">
                        <span class="bar-label">{{ $metric['label'] }}</span>
                        <div class="bar-track"><div class="bar-fill" style="width:{{ $metric['percentage'] }}%"></div></div>
                        <span class="bar-pct">{{ $metric['percentage'] }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- How it works --}}
            <div class="card">
                <div class="card-hdr">
                    <div><div class="card-title">Cara Kerja Sistem</div><div class="card-sub">Pipeline keputusan AI</div></div>
                </div>
                <div class="card-pad">
                    @foreach([
                        ['Data Masuk','Antrian order dikirim real-time dari Service A via API.'],
                        ['Analisis Hybrid','Rule-based checks dan AI reasoning berjalan paralel.'],
                        ['Keputusan','Sistem menghasilkan prioritas, alert, dan rekomendasi.'],
                        ['Adaptasi','Model belajar dari feedback untuk kondisi yang berubah.'],
                    ] as $i => [$t,$d])
                    <div class="step-row">
                        <div class="step-num-b">{{$i+1}}</div>
                        <div><div class="step-t">{{$t}}</div><div class="step-d">{{$d}}</div></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
