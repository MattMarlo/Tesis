<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña</title>
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
    .login-card .card-body { padding: 2rem; }
  </style>
</head>
<body>
  <main class="login-wrapper">
    <div class="card login-card">
      <div class="card-body">
        <div class="text-center mb-4">
          <h3 class="fw-bold">Recuperar Contraseña</h3>
          <p class="text-muted">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
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

        <form action="{{ route('password.email') }}" method="post">
          @csrf
          <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
          </div>
          <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
          <div class="mt-3 text-center">
            <a href="{{ route('login') }}" class="text-decoration-none">Volver al login</a>
          </div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>
