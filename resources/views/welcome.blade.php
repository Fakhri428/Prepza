<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prepza — AI Decision Engine</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#0f1117;color:#e8eaf0;min-height:100vh;overflow-x:hidden}
        /* NAV */
        nav{position:fixed;top:0;left:0;right:0;z-index:50;padding:16px 40px;display:flex;align-items:center;justify-content:space-between;background:rgba(15,17,23,0.85);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.06)}
        .nav-logo{display:flex;align-items:center;gap:10px}
        .nav-logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6c63ff,#00d4aa);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;color:#fff}
        .nav-logo-name{font-weight:700;font-size:15px}
        .nav-logo-sub{font-size:10px;color:#5a6478;margin-top:1px}
        .nav-links{display:flex;align-items:center;gap:12px}
        .btn{display:inline-flex;align-items:center;padding:8px 20px;border-radius:8px;font-size:13.5px;font-weight:600;text-decoration:none;transition:all 0.2s;cursor:pointer;border:none}
        .btn-ghost{color:#8892a4;background:transparent;border:1px solid rgba(255,255,255,0.1)}
        .btn-ghost:hover{color:#e8eaf0;background:rgba(255,255,255,0.06)}
        .btn-primary{background:linear-gradient(135deg,#6c63ff,#5b52e8);color:#fff;box-shadow:0 0 20px rgba(108,99,255,0.3)}
        .btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 24px rgba(108,99,255,0.45)}
        /* HERO */
        .hero{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;position:relative;padding:100px 24px 60px}
        .hero-glow{position:absolute;top:10%;left:50%;transform:translateX(-50%);width:600px;height:400px;background:radial-gradient(ellipse,rgba(108,99,255,0.18) 0%,transparent 70%);pointer-events:none}
        .hero-glow2{position:absolute;bottom:0;right:10%;width:400px;height:300px;background:radial-gradient(ellipse,rgba(0,212,170,0.1) 0%,transparent 70%);pointer-events:none}
        .hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(108,99,255,0.12);border:1px solid rgba(108,99,255,0.3);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:600;color:#6c63ff;margin-bottom:24px}
        .live-dot{width:7px;height:7px;border-radius:50%;background:#00d4aa;animation:pulse 1.5s infinite}
        @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(1.3)}}
        h1{font-size:clamp(36px,6vw,72px);font-weight:800;line-height:1.1;margin-bottom:22px;letter-spacing:-0.02em}
        h1 .gradient{background:linear-gradient(90deg,#6c63ff,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-desc{font-size:17px;color:#8892a4;line-height:1.7;max-width:580px;margin:0 auto 36px}
        .hero-cta{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        .btn-lg{padding:13px 32px;font-size:15px;border-radius:10px}
        .hero-stats{display:flex;gap:40px;justify-content:center;margin-top:60px;flex-wrap:wrap}
        .hero-stat-item{text-align:center}
        .hero-stat-val{font-size:28px;font-weight:800;background:linear-gradient(90deg,#6c63ff,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-stat-label{font-size:12px;color:#5a6478;margin-top:2px}
        .divider{width:1px;height:40px;background:rgba(255,255,255,0.08);align-self:center}
        /* FEATURES */
        section{padding:80px 40px;max-width:1100px;margin:0 auto}
        .section-label{text-align:center;font-size:12px;font-weight:600;color:#6c63ff;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:14px}
        .section-title{text-align:center;font-size:clamp(24px,4vw,36px);font-weight:800;margin-bottom:12px}
        .section-sub{text-align:center;font-size:14.5px;color:#8892a4;max-width:480px;margin:0 auto 48px;line-height:1.6}
        .feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
        .feat-card{background:#1a2035;border:1px solid #2a3550;border-radius:14px;padding:24px;transition:transform 0.2s,box-shadow 0.2s}
        .feat-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,0.25)}
        .feat-icon{font-size:26px;margin-bottom:14px}
        .feat-title{font-size:15px;font-weight:700;margin-bottom:8px}
        .feat-desc{font-size:13px;color:#8892a4;line-height:1.6}
        /* HOW */
        .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:40px}
        .step{text-align:center;padding:20px}
        .step-num{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#00d4aa);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;margin:0 auto 14px}
        .step-title{font-size:14px;font-weight:700;margin-bottom:6px}
        .step-desc{font-size:12.5px;color:#8892a4;line-height:1.5}
        /* CTA BOTTOM */
        .cta-section{text-align:center;padding:80px 24px;background:linear-gradient(135deg,rgba(108,99,255,0.08),rgba(0,212,170,0.05));border-top:1px solid #2a3550;border-bottom:1px solid #2a3550}
        .cta-section h2{font-size:clamp(22px,4vw,36px);font-weight:800;margin-bottom:14px}
        .cta-section p{font-size:14.5px;color:#8892a4;margin-bottom:28px}
        footer{text-align:center;padding:24px;font-size:12px;color:#5a6478;border-top:1px solid #1a2035}
        @media(max-width:768px){.feat-grid,.steps{grid-template-columns:1fr}.hero-stats{gap:20px}.divider{display:none}nav{padding:14px 20px}}
    </style>
</head>
<body>
<nav>
    <div class="nav-logo">
        <div class="nav-logo-icon">P</div>
        <div>
            <div class="nav-logo-name">Prepza</div>
            <div class="nav-logo-sub">AI Decision Engine</div>
        </div>
    </div>
    <div class="nav-links">
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-ghost">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar Gratis</a>
                @endif
            @endauth
        @endif
    </div>
</nav>

<div class="hero">
    <div class="hero-glow"></div>
    <div class="hero-glow2"></div>
    <div style="position:relative;z-index:1">
        <div class="hero-badge"><span class="live-dot"></span> Real-time Active</div>
        <h1>AI-driven <span class="gradient">Decision Engine</span><br>untuk Antrian Cerdas</h1>
        <p class="hero-desc">Prepza mengolah data antrian secara real-time menggunakan pendekatan hybrid rule-based dan AI reasoning — mengoptimalkan prioritas pesanan, mengidentifikasi bottleneck, dan memberikan insight strategis berbasis data.</p>
        <div class="hero-cta">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Buka Dashboard →</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Mulai Sekarang →</a>
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-lg">Sudah punya akun</a>
                @endauth
            @endif
        </div>
        <div class="hero-stats">
            <div class="hero-stat-item"><div class="hero-stat-val">98.7%</div><div class="hero-stat-label">Uptime SLA</div></div>
            <div class="divider"></div>
            <div class="hero-stat-item"><div class="hero-stat-val">&lt;2ms</div><div class="hero-stat-label">Avg. Latency</div></div>
            <div class="divider"></div>
            <div class="hero-stat-item"><div class="hero-stat-val">3.4K</div><div class="hero-stat-label">Request / menit</div></div>
            <div class="divider"></div>
            <div class="hero-stat-item"><div class="hero-stat-val">204+</div><div class="hero-stat-label">Keputusan AI / jam</div></div>
        </div>
    </div>
</div>

<section>
    <div class="section-label">Kapabilitas</div>
    <h2 class="section-title">Satu engine, banyak keputusan</h2>
    <p class="section-sub">Didesain untuk lingkungan dinamis dengan beban kerja yang bervariasi dan terus tumbuh.</p>
    <div class="feat-grid">
        <div class="feat-card">
            <div class="feat-icon">🔄</div>
            <div class="feat-title">Real-time Queue Processing</div>
            <div class="feat-desc">Analisis kondisi antrian secara terus-menerus dengan latensi rendah untuk respons instan terhadap perubahan beban.</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🧠</div>
            <div class="feat-title">AI Reasoning Engine</div>
            <div class="feat-desc">Model AI yang memahami konteks dan pola data historis untuk menghasilkan keputusan yang tepat dan adaptif.</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">⚖️</div>
            <div class="feat-title">Hybrid Rule-Based</div>
            <div class="feat-desc">Kombinasi aturan bisnis deterministik dan AI probabilistik untuk memastikan keputusan yang konsisten dan dapat diprediksi.</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🎯</div>
            <div class="feat-title">Optimasi Prioritas Order</div>
            <div class="feat-desc">Secara otomatis mengurutkan dan memprioritaskan pesanan berdasarkan urgensi, kapasitas, dan kondisi real-time.</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">⚠️</div>
            <div class="feat-title">Deteksi Bottleneck</div>
            <div class="feat-desc">Identifikasi hambatan operasional sebelum menjadi masalah kritis dan rekomendasikan tindakan korektif secara proaktif.</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">📊</div>
            <div class="feat-title">Insight Strategis</div>
            <div class="feat-desc">Laporan dan insight berbasis data yang membantu tim membuat keputusan jangka panjang yang lebih baik.</div>
        </div>
    </div>
</section>

<section style="padding-bottom:0">
    <div class="section-label">Cara Kerja</div>
    <h2 class="section-title">Dari data ke keputusan dalam milidetik</h2>
    <div class="steps">
        <div class="step"><div class="step-num">1</div><div class="step-title">Data Masuk</div><div class="step-desc">Service A mengirim data antrian order secara real-time ke Prepza melalui API terstruktur.</div></div>
        <div class="step"><div class="step-num">2</div><div class="step-title">Analisis AI</div><div class="step-desc">Engine menjalankan rule-based checks dan AI reasoning secara paralel untuk menilai kondisi antrian.</div></div>
        <div class="step"><div class="step-num">3</div><div class="step-title">Keputusan</div><div class="step-desc">Sistem menghasilkan keputusan prioritas, peringatan bottleneck, dan rekomendasi tindakan operasional.</div></div>
        <div class="step"><div class="step-num">4</div><div class="step-title">Adaptasi</div><div class="step-desc">Model terus belajar dari feedback dan menyesuaikan strategi untuk kondisi beban kerja yang berubah.</div></div>
    </div>
</section>

<div style="padding:80px 24px" class="cta-section">
    <h2>Siap optimalkan operasi Anda?</h2>
    <p>Bergabung dan nikmati kekuatan AI Decision Engine untuk antrian yang lebih cerdas.</p>
    @if (Route::has('register'))
        @guest
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Daftar Sekarang — Gratis</a>
        @endguest
        @auth
            <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Buka Dashboard →</a>
        @endauth
    @endif
</div>

<footer>© {{ date('Y') }} Prepza · AI-driven Decision Engine · Prepza</footer>
</body>
</html>
