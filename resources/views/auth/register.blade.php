<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar — Prepza</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#0f1117;color:#e8eaf0;min-height:100vh;display:flex;align-items:stretch}
        .left{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;position:relative;overflow:hidden}
        .left-bg{position:absolute;inset:0;background:linear-gradient(135deg,rgba(0,212,170,0.08) 0%,rgba(108,99,255,0.1) 100%)}
        .left-glow{position:absolute;top:-80px;right:-80px;width:400px;height:400px;background:radial-gradient(circle,rgba(0,212,170,0.15),transparent 70%)}
        .left-content{position:relative;z-index:1;max-width:440px;width:100%}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:36px}
        .brand-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#6c63ff,#00d4aa);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;color:#fff}
        .brand-name{font-weight:700;font-size:16px}
        .brand-sub{font-size:11px;color:#5a6478}
        h1{font-size:26px;font-weight:800;margin-bottom:8px}
        .sub{font-size:14px;color:#8892a4;margin-bottom:28px;line-height:1.5}
        .form-group{margin-bottom:16px}
        label{display:block;font-size:13px;font-weight:600;color:#c0c8d8;margin-bottom:6px}
        input{width:100%;background:#1a2035;border:1px solid #2a3550;border-radius:9px;padding:11px 14px;font-size:14px;color:#e8eaf0;outline:none;transition:border-color 0.2s,box-shadow 0.2s;font-family:'Inter',sans-serif}
        input:focus{border-color:#00d4aa;box-shadow:0 0 0 3px rgba(0,212,170,0.12)}
        input::placeholder{color:#3d4a5f}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,#00d4aa,#00b894);color:#0f1117;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.2s;font-family:'Inter',sans-serif;box-shadow:0 4px 20px rgba(0,212,170,0.25);margin-top:6px}
        .btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(0,212,170,0.4)}
        .login-link{text-align:center;margin-top:20px;font-size:13.5px;color:#8892a4}
        .login-link a{color:#00d4aa;font-weight:600;text-decoration:none}
        .login-link a:hover{text-decoration:underline}
        .error-box{background:rgba(255,107,107,0.1);border:1px solid rgba(255,107,107,0.25);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#ff9999}
        .terms-wrap{font-size:12.5px;color:#8892a4;margin-top:12px;line-height:1.6;text-align:center}
        .terms-wrap a{color:#6c63ff;text-decoration:none}
        .terms-wrap a:hover{text-decoration:underline}
        /* RIGHT */
        .right{width:380px;background:#161b27;border-left:1px solid #2a3550;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:40px;gap:18px}
        .feat-item{display:flex;align-items:flex-start;gap:12px}
        .feat-icon-wrap{width:36px;height:36px;border-radius:9px;background:rgba(108,99,255,0.12);border:1px solid rgba(108,99,255,0.2);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
        .feat-title{font-size:13.5px;font-weight:700;margin-bottom:3px}
        .feat-desc{font-size:12px;color:#5a6478;line-height:1.5}
        .right-title{font-size:16px;font-weight:800;margin-bottom:6px}
        .right-sub{font-size:12.5px;color:#5a6478;margin-bottom:8px;line-height:1.5}
        @media(max-width:768px){.right{display:none}.grid-2{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="left">
    <div class="left-bg"></div>
    <div class="left-glow"></div>
    <div class="left-content">
        <div class="brand">
            <div class="brand-icon">P</div>
            <div>
                <div class="brand-name">Prepza</div>
                <div class="brand-sub">AI Decision Engine</div>
            </div>
        </div>
        <h1>Buat Akun Baru</h1>
        <p class="sub">Daftarkan diri untuk mengakses Intelligence Dashboard dan fitur analitik antrian.</p>

        @if ($errors->any())
            <div class="error-box">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" required autofocus autocomplete="name">
            </div>
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@example.com" required autocomplete="username">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Min. 8 karakter" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi password" required autocomplete="new-password">
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="form-group" style="margin-top:4px">
                    <label style="display:flex;align-items:flex-start;gap:9px;cursor:pointer;font-size:12.5px;color:#8892a4;font-weight:400">
                        <input type="checkbox" name="terms" id="terms" required style="width:15px;height:15px;margin-top:1px;accent-color:#6c63ff;cursor:pointer">
                        <span>Saya menyetujui <a href="{{ route('terms.show') }}" target="_blank" style="color:#6c63ff">Syarat Layanan</a> dan <a href="{{ route('policy.show') }}" target="_blank" style="color:#6c63ff">Kebijakan Privasi</a></span>
                    </label>
                </div>
            @endif

            <button type="submit" class="btn-submit">Buat Akun Sekarang</button>
        </form>

        <div class="login-link">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></div>
    </div>
</div>

<div class="right">
    <div>
        <div class="right-title">Mengapa Prepza?</div>
        <div class="right-sub">Platform terpadu untuk operasi antrian yang lebih cerdas dan efisien.</div>
    </div>
    <div class="feat-item">
        <div class="feat-icon-wrap">🔄</div>
        <div><div class="feat-title">Analisis Real-time</div><div class="feat-desc">Pantau kondisi antrian secara langsung dengan latensi kurang dari 2ms.</div></div>
    </div>
    <div class="feat-item">
        <div class="feat-icon-wrap">🧠</div>
        <div><div class="feat-title">AI Reasoning</div><div class="feat-desc">Mesin keputusan cerdas yang mempelajari pola dan mengoptimalkan strategi secara otomatis.</div></div>
    </div>
    <div class="feat-item">
        <div class="feat-icon-wrap">⚠️</div>
        <div><div class="feat-title">Deteksi Bottleneck</div><div class="feat-desc">Alert proaktif sebelum hambatan operasional menjadi masalah serius.</div></div>
    </div>
    <div class="feat-item">
        <div class="feat-icon-wrap">📊</div>
        <div><div class="feat-title">Insight Strategis</div><div class="feat-desc">Dashboard visual dan laporan mendalam untuk pengambilan keputusan berbasis data.</div></div>
    </div>
</div>
</body>
</html>
