@extends('layouts.main')

@section('header','Editar Pre-reserva')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Editar Pre-reserva</h2>
        <a href="{{ route('prereservas.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card shadow-sm p-4">
        <form action="{{ route('prereservas.update', $preReserva->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="row gy-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo del cliente</label>
                    <input type="text" name="cliente_nombre" class="form-control" value="{{ old('cliente_nombre', $preReserva->cliente_nombre) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $preReserva->email) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cédula</label>
                    <input type="text" name="cedula" class="form-control" value="{{ old('cedula', $preReserva->cedula) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destino</label>
                    <input type="text" name="destino" class="form-control" value="{{ old('destino', $preReserva->destino) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de viaje</label>
                    <input type="date" name="fecha_viaje" class="form-control" value="{{ old('fecha_viaje', $preReserva->fecha_viaje ? $preReserva->fecha_viaje->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $preReserva->telefono) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        @foreach(['pendiente_contacto','contactado','convertida','perdida'] as $estado)
                            <option value="{{ $estado }}" {{ old('estado', $preReserva->estado) === $estado ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection