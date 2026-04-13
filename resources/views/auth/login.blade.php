<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Prepza</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#0f1117;color:#e8eaf0;min-height:100vh;display:flex;align-items:stretch}
        .left{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;position:relative;overflow:hidden}
        .left-bg{position:absolute;inset:0;background:linear-gradient(135deg,rgba(108,99,255,0.12) 0%,rgba(0,212,170,0.06) 100%)}
        .left-glow{position:absolute;top:-100px;left:-100px;width:500px;height:500px;background:radial-gradient(circle,rgba(108,99,255,0.2),transparent 70%)}
        .left-content{position:relative;z-index:1;max-width:420px;width:100%}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:40px}
        .brand-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#6c63ff,#00d4aa);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;color:#fff}
        .brand-name{font-weight:700;font-size:16px}
        .brand-sub{font-size:11px;color:#5a6478}
        h1{font-size:28px;font-weight:800;margin-bottom:8px}
        .sub{font-size:14px;color:#8892a4;margin-bottom:32px;line-height:1.5}
        .form-group{margin-bottom:18px}
        label{display:block;font-size:13px;font-weight:600;color:#c0c8d8;margin-bottom:7px}
        input{width:100%;background:#1a2035;border:1px solid #2a3550;border-radius:9px;padding:11px 14px;font-size:14px;color:#e8eaf0;outline:none;transition:border-color 0.2s,box-shadow 0.2s;font-family:'Inter',sans-serif}
        input:focus{border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,0.15)}
        input::placeholder{color:#3d4a5f}
        .row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
        .checkbox-wrap{display:flex;align-items:center;gap:8px;font-size:13px;color:#8892a4;cursor:pointer}
        .checkbox-wrap input[type=checkbox]{width:16px;height:16px;accent-color:#6c63ff;cursor:pointer}
        .forgot{font-size:13px;color:#6c63ff;text-decoration:none;font-weight:500}
        .forgot:hover{text-decoration:underline}
        .btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,#6c63ff,#5b52e8);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.2s;font-family:'Inter',sans-serif;box-shadow:0 4px 20px rgba(108,99,255,0.3)}
        .btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(108,99,255,0.45)}
        .register-link{text-align:center;margin-top:22px;font-size:13.5px;color:#8892a4}
        .register-link a{color:#6c63ff;font-weight:600;text-decoration:none}
        .register-link a:hover{text-decoration:underline}
        .error-box{background:rgba(255,107,107,0.1);border:1px solid rgba(255,107,107,0.25);border-radius:8px;padding:10px 14px;margin-bottom:18px;font-size:13px;color:#ff9999}
        .success-box{background:rgba(0,212,170,0.1);border:1px solid rgba(0,212,170,0.25);border-radius:8px;padding:10px 14px;margin-bottom:18px;font-size:13px;color:#00d4aa}
        /* RIGHT PANEL */
        .right{width:420px;background:#161b27;border-left:1px solid #2a3550;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;gap:24px}
        .right-stat{background:#1a2035;border:1px solid #2a3550;border-radius:12px;padding:18px 22px;width:100%;text-align:center}
        .right-stat-val{font-size:26px;font-weight:800;background:linear-gradient(90deg,#6c63ff,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:4px}
        .right-stat-label{font-size:12px;color:#5a6478}
        .right-title{font-size:14px;font-weight:700;color:#c0c8d8;text-align:center}
        .right-desc{font-size:12.5px;color:#5a6478;text-align:center;line-height:1.6}
        @media(max-width:768px){.right{display:none}}
    </style>
</head>
<body>
<div class="left">
    <div class="left-bg"></div>
    <div class="left-glow"></div>
    <div class="left-content">
        <div class="brand">
            <div class="brand-icon">SB</div>
            <div>
                <div class="brand-name">Prepza</div>
                <div class="brand-sub">AI Decision Engine</div>
            </div>
        </div>
        <h1>Selamat Datang Kembali</h1>
        <p class="sub">Masuk untuk mengakses Intelligence Dashboard dan monitoring antrian real-time.</p>

        @if ($errors->any())
            <div class="error-box">{{ $errors->first() }}</div>
        @endif

        @session('status')
            <div class="success-box">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@example.com" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <div class="row">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="remember" id="remember_me">
                    <span>Ingat saya</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot">Lupa password?</a>
                @endif
            </div>
            <button type="submit" class="btn-submit">Masuk ke Dashboard</button>
        </form>

        @if (Route::has('register'))
            <div class="register-link">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></div>
        @endif
    </div>
</div>
<div class="right">
    <div class="right-title">AI Decision Engine</div>
    <div class="right-desc">Platform cerdas untuk optimasi antrian order secara real-time dengan pendekatan hybrid rule-based dan AI reasoning.</div>
    <div class="right-stat"><div class="right-stat-val">98.7%</div><div class="right-stat-label">System Uptime</div></div>
    <div class="right-stat"><div class="right-stat-val">&lt;2ms</div><div class="right-stat-label">Avg. Decision Latency</div></div>
    <div class="right-stat"><div class="right-stat-val">204+</div><div class="right-stat-label">AI Decisions / Hour</div></div>
    <div class="right-desc" style="margin-top:8px">Ditenagai oleh teknologi machine learning adaptif untuk beban kerja yang terus berubah.</div>
</div>
</body>
</html>
