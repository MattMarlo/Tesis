<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            border-radius: 15px;
        }

        .form-control, .form-select {
            border-radius: 10px;
        }

        .btn {
            border-radius: 10px;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8">

            <div class="card shadow border-0">
                <div class="card-body p-4">

                    <h2 class="fw-bold text-center mb-4">Crear Cuenta</h2>

                    <form action="{{ route('register') }}" method="post">
                        @csrf

                        <div class="row g-3">

                            <!-- Nombres -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nombres</label>
                                <input type="text" name="nombres" class="form-control" value="{{ old('nombres') }}" required>
                                @error('nombres')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Apellidos -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" value="{{ old('apellidos') }}" required>
                                @error('apellidos')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Teléfono -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}" required>
                                @error('telefono')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Documento -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Documento</label>
                                <input type="text" name="documento" class="form-control" value="{{ old('documento') }}" required>
                                @error('documento')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Rol -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Rol</label>
                                <select name="rol" class="form-select" required>
                                    <option value="">Seleccionar rol...</option>
                                    <option value="agente" {{ old('rol') == 'agente' ? 'selected' : '' }}>Agente</option>
                                    <option value="admin" {{ old('rol') == 'admin' ? 'selected' : '' }}>Administrador</option>
                                </select>
                                @error('rol')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                                @error('password')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>

                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('login') }}" class="text-decoration-none text-primary">
                                ¿Ya tienes cuenta? Inicia sesión
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus me-1"></i> Crear Cuenta
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>