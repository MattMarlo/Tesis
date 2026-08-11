@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link rel="stylesheet" href="{{ asset('css/operacion-viaje.css') }}">
<link rel="stylesheet" href="{{ asset('css/gestion-habitaciones-alojamiento.css') }}?v={{ filemtime(public_path('css/gestion-habitaciones-alojamiento.css')) }}">

@php
    $urlRegreso = route('operaciones.show', $reserva->id)
        . ($tarea ? '#tarea-itinerario-'.$tarea->id : '#tareasItinerario');
    $total = $personas->count();
    $ocupados = count($asignados);
    $capacidad = $alojamiento->habitaciones->sum('capacidad');
    $estado = match ($alojamiento->estado) {
        'confirmado' => 'Confirmado',
        'cancelado' => 'Cancelado',
        default => 'Pendiente',
    };
@endphp

<main id="main" class="main pagina-operacion-viaje pagina-habitaciones">
    <header class="expediente-encabezado">
        <div>
            <a href="{{ $urlRegreso }}" class="volver-operaciones">
                <i class="bi bi-arrow-left"></i>
                {{ $tarea ? 'Volver a la tarea del itinerario' : 'Volver al expediente' }}
            </a>
            <span class="expediente-modulo">Distribución de hospedaje</span>
            <h1>{{ $alojamiento->nombre_hotel }}</h1>
            <p>{{ $alojamiento->ciudad }}, {{ $alojamiento->pais }}</p>
        </div>
        <span class="estado-alojamiento estado-{{ $alojamiento->estado }}">{{ $estado }}</span>
    </header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="resumen-habitaciones">
        <div><span>Entrada</span><strong>{{ $alojamiento->fecha_hora_entrada->format('d/m/Y H:i') }}</strong></div>
        <div><span>Salida</span><strong>{{ $alojamiento->fecha_hora_salida->format('d/m/Y H:i') }}</strong></div>
        <div><span>Habitaciones</span><strong>{{ $alojamiento->habitaciones->count() }}</strong></div>
        <div><span>Capacidad</span><strong>{{ $capacidad }}</strong></div>
        <div><span>Viajeros asignados</span><strong>{{ $ocupados }} de {{ $total }}</strong></div>
    </section>

    <div class="distribucion-grid">
        <section class="panel-habitaciones">
            <div class="panel-titulo">
                <div><span>Ocupación</span><h2>Habitaciones registradas</h2></div>
            </div>

            <div class="lista-habitaciones">
                @forelse ($alojamiento->habitaciones as $habitacion)
                    <article class="tarjeta-habitacion">
                        <header>
                            <div>
                                <small>Habitación</small>
                                <h3>{{ $habitacion->referencia ?: ucfirst($habitacion->tipo) }}</h3>
                                <span>{{ ucfirst($habitacion->tipo) }}</span>
                            </div>
                            <strong>{{ $habitacion->asignaciones->count() }}/{{ $habitacion->capacidad }}</strong>
                        </header>

                        <ul>
                            @forelse ($habitacion->asignaciones as $asignacion)
                                <li>
                                    <span>{{ $asignacion->viajeroReserva?->nombre_completo ?? $asignacion->cliente?->nombre_completo ?? 'Viajero' }}</span>
                                    @if ($editable)
                                        <form method="POST" action="{{ route('operaciones.habitaciones.retirar', $asignacion) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Retirar de la habitación"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    @endif
                                </li>
                            @empty
                                <li class="habitacion-vacia">Sin viajeros asignados</li>
                            @endforelse
                        </ul>

                        @if ($editable && $habitacion->asignaciones->count() < $habitacion->capacidad)
                            <form method="POST" action="{{ route('operaciones.habitaciones.asignar', $habitacion) }}" class="form-asignar">
                                @csrf
                                <select name="persona" onchange="const [tipo,id]=this.value.split('-'); this.form.querySelector('[name=viajero_reserva_id]').value=tipo==='viajero'?id:''; this.form.querySelector('[name=cliente_id]').value=tipo==='cliente'?id:''" required>
                                    <option value="">Seleccionar viajero</option>
                                    @foreach ($personas as $persona)
                                        @php $clave = $persona['tipo'].'-'.$persona['id']; @endphp
                                        @if (!in_array($clave, $asignados, true))
                                            <option value="{{ $clave }}">{{ $persona['nombre'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <input type="hidden" name="viajero_reserva_id">
                                <input type="hidden" name="cliente_id">
                                <button type="submit">Asignar</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="sin-habitaciones">Crea la primera habitación para comenzar la distribución.</div>
                @endforelse
            </div>
        </section>

        <aside class="panel-crear-habitacion">
            <span>Nueva habitación</span>
            <h2>Agregar espacio</h2>
            @if ($editable)
                <form id="formularioCrearHabitacion" method="POST" action="{{ route('operaciones.habitaciones.store', $alojamiento) }}" novalidate>
                    @csrf
                    <label>Tipo</label>
                    <select id="habitacionTipo" name="tipo" required>
                        @foreach (\App\Models\HabitacionAlojamiento::CAPACIDADES as $tipo => $capacidadTipo)
                            <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ ucfirst($tipo) }} ({{ $capacidadTipo }})</option>
                        @endforeach
                    </select>
                    @error('tipo')<small class="campo-error-habitacion">{{ $message }}</small>@enderror
                    <label>Número o nombre *</label>
                    <input id="habitacionReferencia" name="referencia" maxlength="100" value="{{ old('referencia') }}" placeholder="Ejemplo: 301" required>
                    @error('referencia')<small class="campo-error-habitacion">{{ $message }}</small>@enderror
                    <label>Camas y observaciones</label>
                    <textarea id="habitacionObservaciones" name="observaciones" minlength="3" maxlength="1000" rows="4" placeholder="Ejemplo: dos camas individuales">{{ old('observaciones') }}</textarea>
                    @error('observaciones')<small class="campo-error-habitacion">{{ $message }}</small>@enderror
                    <button type="submit"><i class="bi bi-plus-lg"></i> Crear habitación</button>
                </form>
            @else
                <p>El expediente no admite modificaciones.</p>
            @endif
        </aside>
    </div>
</main>

<script src="{{ asset('js/gestion-habitaciones-alojamiento.js') }}?v={{ filemtime(public_path('js/gestion-habitaciones-alojamiento.js')) }}"></script>
@endsection
