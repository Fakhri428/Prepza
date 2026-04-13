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
                <a href="{{ url('/intelligence/dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--accent),#5b52e8);color:#fff;padding:9px 18px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;margin-top:14px;box-shadow:0 4px 14px rgba(108,99,255,0.3)">
                    🧠 Buka Intelligence Dashboard →
                </a>
            </div>
            <div style="display:flex;gap:12px;flex-shrink:0">
                @foreach([['98.7%','Uptime','#6c63ff'],['<2ms','Latency','#10b981'],['3.4K','Req/mnt','#f59e0b']] as [$v,$l,$c])
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:14px 18px;text-align:center;min-width:80px">
                    <div style="font-size:20px;font-weight:800;color:{{$c}}">{{$v}}</div>
                    <div style="font-size:10.5px;color:var(--text-dim);margin-top:2px">{{$l}}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- STAT CARDS --}}
        <div class="db-grid-4">
            @foreach([
                ['Total Antrian','1,245','🗂️','rgba(108,99,255,0.12)','+12%','up'],
                ['Sedang Diproses','310','⚙️','rgba(0,212,170,0.1)','+5%','up'],
                ['Priority High','48','⚠️','rgba(239,68,68,0.1)','-3%','down'],
                ['Selesai Hari Ini','887','✅','rgba(16,185,129,0.1)','+8%','up'],
            ] as [$label,$val,$icon,$ibg,$change,$dir])
            <div class="card card-pad">
                <div style="display:flex;align-items:flex-start;justify-content:space-between">
                    <div>
                        <div class="stat-label">{{$label}}</div>
                        <div class="stat-num" style="color:var(--text-main)">{{$val}}</div>
                        <div class="stat-change {{$dir==='up'?'change-up':'change-down'}}">
                            {{$dir==='up'?'↑':'↓'}} {{$change}} vs kemarin
                        </div>
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
                        @foreach([35,52,48,70,45,60,75,88,65,55,80,90,72,68,85,92,78,65,55,70,82,75,60,45] as $h)
                        <div class="chart-bar" style="height:{{$h}}%"></div>
                        @endforeach
                    </div>
                    <div class="chart-labels">
                        @foreach(['00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'] as $lbl)
                        <span class="chart-lbl" style="display:{{($loop->index % 3 === 0)?'block':'none'}}">{{$lbl}}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Donut Chart --}}
            <div class="card">
                <div class="card-hdr">
                    <div><div class="card-title">Distribusi Keputusan AI</div><div class="card-sub">Berdasarkan metode reasoning</div></div>
                </div>
                <div class="donut-wrap">
                    <svg class="donut-svg" width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="38" fill="none" stroke="var(--border)" stroke-width="16"/>
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#6c63ff" stroke-width="16"
                            stroke-dasharray="95 144" stroke-dashoffset="0" transform="rotate(-90 50 50)"/>
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#00d4aa" stroke-width="16"
                            stroke-dasharray="67 172" stroke-dashoffset="-95" transform="rotate(-90 50 50)"/>
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#f59e0b" stroke-width="16"
                            stroke-dasharray="48 191" stroke-dashoffset="-162" transform="rotate(-90 50 50)"/>
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#ef4444" stroke-width="16"
                            stroke-dasharray="29 210" stroke-dashoffset="-210" transform="rotate(-90 50 50)"/>
                        <text x="50" y="47" text-anchor="middle" font-size="10" font-weight="700" fill="var(--text-main)">AI</text>
                        <text x="50" y="57" text-anchor="middle" font-size="7" fill="var(--text-dim)">Engine</text>
                    </svg>
                    <div>
                        @foreach([['#6c63ff','Rule-based','40%'],['#00d4aa','AI Reasoning','28%'],['#f59e0b','Hybrid','20%'],['#ef4444','Manual','12%']] as [$c,$l,$p])
                        <div class="legend-item">
                            <div class="legend-dot" style="background:{{$c}}"></div>
                            <span style="color:var(--text-muted)">{{$l}}</span>
                            <span style="font-weight:600;color:var(--text-main);margin-left:auto">{{$p}}</span>
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
                    <div><div class="card-title">Kapabilitas AI Engine</div><div class="card-sub">Hybrid Rule-Based + AI Reasoning</div></div>
                </div>
                <div class="card-pad">
                    @foreach([['Analisis Kondisi Real-time',94],['Optimasi Prioritas Pesanan',88],['Deteksi Bottleneck',91],['Insight Strategis',79],['Skalabilitas Adaptif',96]] as [$lbl,$pct])
                    <div class="bar-row">
                        <span class="bar-label">{{$lbl}}</span>
                        <div class="bar-track"><div class="bar-fill" style="width:{{$pct}}%"></div></div>
                        <span class="bar-pct">{{$pct}}%</span>
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
