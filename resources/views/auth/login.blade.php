<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriDash | Iniciar Sesion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e2a1e 0%, #2e7d32 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            background: #fff; border-radius: 20px; padding: 2.5rem;
            width: 100%; max-width: 420px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .login-title { font-weight: 800; color: #2e7d32; }
        .login-sub { color: #6c757d; }
        .btn-login { background: #2e7d32; border: none; font-weight: 600; }
        .btn-login:hover { background: #1b5e20; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div style="font-size:3rem;">🌱</div>
            <h1 class="login-title">AgriDash</h1>
            <p class="login-sub">Sistema de Gestion • Ecofillers</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">👤 Usuario</label>
                <input type="text" name="username" class="form-control" placeholder="Ingresa tu username" required value="{{ old('username') }}">
            </div>
<div class="mb-3">
                <label class="form-label">🔒 Contrasena</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-login btn-lg w-100 text-white">INICIAR SESION</button>
        </form>
    </div>
</body>
</html>
