@extends('layouts.main')

@section('content')
<div class="container">
    <h2>Pre-reservas</h2>
    <a href="{{ route('prereservas.create') }}" class="btn btn-primary">Nueva Pre-reserva</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Cédula</th>
                <th>Destino</th>
                <th>Fecha viaje</th>
                <th>Origen</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($preReservas as $pre)
            <tr>
                <td>{{ $pre->id }}</td>
                <td>{{ $pre->cliente_nombre }}</td>
                <td>{{ $pre->cedula }}</td>
                <td>{{ $pre->destino }}</td>
                <td>{{ $pre->fecha_viaje }}</td>
                <td>{{ $pre->origen }}</td>
                <td>{{ $pre->estado }}</td>
                <td>
                    @if(!$pre->reserva_id)
                        <form method="POST" action="{{ route('prereservas.convertir', $pre->id) }}" style="display:inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Convertir a Reserva</button>
                        </form>
                        <form method="POST" action="{{ route('prereservas.destroy', $pre->id) }}" style="display:inline" onsubmit="return confirm('¿Está seguro de que desea eliminar esta pre-reserva?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    @else
                        <span class="badge bg-success">Reservada</span>
                        <form method="POST" action="{{ route('prereservas.destroy', $pre->id) }}" style="display:inline" onsubmit="return confirm('¿Está seguro de que desea eliminar esta pre-reserva?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
