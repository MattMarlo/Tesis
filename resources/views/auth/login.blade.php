<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - TravelManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: linear-gradient(150deg, #1F4068 0%, #183A67 50%, #0F2746 100%);
      min-height: 100vh;
      color: #fff;
    }
    .login-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .login-card {
      width: 100%;
      max-width: 430px;
      background: rgba(255,255,255,0.85);
      border-radius: 1rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      color: #111;
    }
    .login-card .card-body {
      padding: 2rem;
    }
    .login-logo {
      width: 65px;
      height: 65px;
      background: #1F4068;
      color: #fff;
      border-radius: 0.75rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      margin-bottom: 0.85rem;
      font-size: 1.25rem;
    }
  </style>
</head>
<body>
  <main class="login-wrapper">
    <div class="card login-card">
      <div class="card-body">
        <div class="text-center mb-4">
          <div class="login-logo">TM</div>
          <h3 class="fw-bold">TravelManager</h3>
          <p class="text-muted">Inicia sesión para continuar</p>
        </div>

        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('login') }}" method="post" novalidate>
          @csrf
          <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
          <div class="mt-3 text-center">
            <small class="text-muted">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></small>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>