<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SIMASU Admin</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <div class="hero">
        <div class="logo-wrap flex flex-col items-center">
            <img src="{{ asset('logo.png') }}" alt="SIMASU Logo" class="logo">
            <div class="brand">SIMASU</div>
        </div>
        <h1>Selamat Datang Kembali</h1>
        <div class="sub">Silakan masukkan kredensial admin Anda untuk melanjutkan ke dashboard pengelolaan.</div>
    </div>

    <div class="shell">
        <div class="card">
            
            @if ($errors->any())
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert" style="margin-bottom: 20px;">
                    <span class="font-medium">Gagal!</span> {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert" style="margin-bottom: 20px;">
                    <span class="font-medium">Sukses!</span> {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('login.process') }}" method="POST">
                @csrf
                
                <div class="form-group mb-4">
                    <label class="label" for="email">Alamat Email</label>
                    <div class="input-wrap">
                        <input type="email" name="email" id="email" class="input" 
                               placeholder="admin@indrayuda.my.id" 
                               value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="form-group mb-6">
                    <label class="label" for="password">Kata Sandi</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="password" class="input" 
                               placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="width: 100%;">Masuk</button>
            
            </form>
            
            <div style="text-align: center; margin-top: 24px; color: #666; font-size: 14px;">
                &copy; {{ date('Y') }} SIMASU. All rights reserved.
            </div>

        </div>
    </div>

</body>
</html>