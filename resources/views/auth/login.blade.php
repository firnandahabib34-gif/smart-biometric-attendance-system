{{-- ============================================================
     HALAMAN LOGIN — Smart Biometric Attendance System
     Layout : Split screen (kiri branding, kanan form)
    Theme  : Forest green, lime, and soft neutral surfaces
     ============================================================ --}}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Smart Biometric Attendance</title>

    {{-- Font modern --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Vite: Tailwind and application scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Style khusus halaman login --}}
    <style>
        /* Font utama */
        body { font-family: 'DM Sans', sans-serif; }

        /* Keep the sign-in surface consistent with the admin workspace. */
        .login-bg {
            background-color: #f5f7f3;
            background-image: linear-gradient(#e9eee7 1px, transparent 1px), linear-gradient(90deg, #e9eee7 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* Login panel */
        .login-card {
            background: #fff;
            box-shadow: 0 18px 48px -24px rgba(16, 47, 41, 0.28);
        }

        /* Input modern */
        .input-modern {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }
        .input-modern:focus {
            border-color: #527c69;
            box-shadow: 0 0 0 4px rgba(82, 124, 105, 0.12);
            outline: none;
        }

        /* Primary action */
        .btn-login {
            background: #153a32;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(21, 58, 50, 0.22);
        }

        /* Ikon sidik jari berdenyut */
        .fingerprint-icon {
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.05); }
        }

        /* Animasi muncul saat halaman dibuka */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">

    {{-- Wrapper utama: 2 kolom di layar besar, 1 kolom di mobile --}}
    <div class="w-full max-w-5xl grid lg:grid-cols-2 rounded-lg overflow-hidden shadow-xl fade-in">

        {{-- ============================================================
             KOLOM KIRI — BRANDING (hanya muncul di layar besar)
             ============================================================ --}}
        <div class="hidden lg:flex flex-col justify-between p-12 bg-[#153a32] text-white relative">

            {{-- Logo + nama aplikasi --}}
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Smart Biometric</h1>
                        <p class="text-xs text-white/70">Attendance System</p>
                    </div>
                </div>
            </div>

            {{-- Headline + deskripsi --}}
            <div class="relative z-10 my-8">
                <h2 class="text-4xl font-bold leading-tight mb-4">
                    Sistem Absensi<br>
                    <span class="text-[#d7ed76]">
                        Berbasis IoT
                    </span>
                </h2>
                <p class="text-white/80 text-sm leading-relaxed mb-8">
                    Portal administrasi mahasiswa dan kehadiran untuk pengelola Smart Biometric.
                </p>

                {{-- Daftar fitur unggulan --}}
                <div class="space-y-3">
                    @foreach ([
                        'Manajemen data mahasiswa',
                        'Pencatatan dan rekap absensi',
                        'Ekspor laporan bulanan CSV'
                    ] as $fitur)
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span>{{ $fitur }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer branding --}}
            <div class="relative z-10 text-xs text-white/60">
                © 2026 Politeknik Negeri Batam
            </div>
        </div>

        {{-- ============================================================
             KOLOM KANAN — FORM LOGIN
             ============================================================ --}}
        <div class="login-card p-8 lg:p-12 flex flex-col justify-center">

            {{-- Logo kecil (khusus mobile) --}}
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <div class="w-12 h-12 rounded-xl bg-[#153a32] flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-bold text-lg">Smart Biometric</h1>
                    <p class="text-xs text-gray-500">Attendance System</p>
                </div>
            </div>

            {{-- Ikon sidik jari berdenyut --}}
            <div class="flex justify-center mb-6">
                <div class="fingerprint-icon w-20 h-20 rounded-full bg-[#edf3e9] flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-emerald-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                    </svg>
                </div>
            </div>

            {{-- Judul form --}}
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Selamat Datang</h2>
                <p class="text-sm text-gray-500">Masuk ke dashboard admin</p>
            </div>

            {{-- Notifikasi status (misal setelah reset password) --}}
            @if (session('status'))
                <div class="mb-4 p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ============================
                 FORM LOGIN
                 ============================ --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Field Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email Address
                    </label>
                    <div class="relative">
                        {{-- Ikon email --}}
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input id="email"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               autocomplete="username"
                               placeholder="admin@smartbiometric.test"
                               class="input-modern w-full pl-12 pr-4 py-3 rounded-xl bg-gray-50 text-gray-800 placeholder-gray-400">
                    </div>
                    {{-- Pesan error email --}}
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field Password --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        {{-- Ikon gembok --}}
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password"
                               type="password"
                               name="password"
                               required
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="input-modern w-full pl-12 pr-12 py-3 rounded-xl bg-gray-50 text-gray-800 placeholder-gray-400">

                        {{-- Tombol show/hide password --}}
                        <button type="button"
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    {{-- Pesan error password --}}
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember Me + Lupa Password --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember"
                               class="w-4 h-4 rounded border-gray-300 text-emerald-800 focus:ring-emerald-700">
                        <span class="text-sm text-gray-600">Ingat saya</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-sm text-emerald-800 hover:text-emerald-950 font-medium">
                            Lupa password?
                        </a>
                    @endif
                </div>

                {{-- Tombol Submit --}}
                <button type="submit"
                        class="btn-login w-full py-3.5 rounded-xl text-white font-semibold text-base shadow-lg">
                    <span class="flex items-center justify-center gap-2">
                        Masuk ke Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </span>
                </button>
            </form>

            {{-- Footer kecil --}}
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-400">
                    Sistem Absensi IoT — Teknik Informatika Polibatam
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SCRIPT: Toggle show/hide password
         ============================================================ --}}
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eye-icon');

            if (input.type === 'password') {
                // Tampilkan password
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                // Sembunyikan password
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        }
    </script>
</body>
</html>