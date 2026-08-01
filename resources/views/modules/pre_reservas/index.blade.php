@extends('layouts.main')

@section('content')
<div class="container">
    <h2>Pre-reservas</h2>
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
                <th>Email</th>
                <th>Teléfono</th>
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
                <td>{{ $pre->email }}</td>
                <td>{{ $pre->telefono }}</td>
                <td>{{ $pre->destino }}</td>
                <td>{{ $pre->fecha_viaje }}</td>
                <td>{{ $pre->origen }}</td>
                <td>{{ $pre->estado }}</td>
                <td>
                    <a href="{{ route('prereservas.edit', $pre->id) }}" class="btn btn-sm btn-primary">Editar</a>
                    @if (
                        !$pre->reserva_id &&
                        in_array(
                            $pre->estado,
                            ['pendiente_contacto', 'contactado'],
                            true
                        )
                    )
                        <form
                            method="POST"
                            action="{{ route(
                                'prereservas.convertir',
                                $pre->id
                            ) }}"
                            class="d-inline formulario-convertir-prerreserva"
                        >
                            @csrf

                            <button
                                type="button"
                                class="btn btn-sm btn-success btn-convertir-prerreserva"
                            >
                                Convertir en reserva
                            </button>
                        </form>
                    @elseif ($pre->reserva_id)
                        <span class="badge bg-success">
                            Reserva generada
                        </span>
                    @else
                        <span class="badge bg-secondary">
                            No disponible
                        </span>
                    @endif
                    <form method="POST" action="{{ route('prereservas.destroy', $pre->id) }}" style="display:inline" onsubmit="return confirm('¿Está seguro de que desea eliminar esta pre-reserva?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<script>
    $(function () {
        $(document).on(
            'click',
            '.btn-convertir-prerreserva',
            function () {
                const formulario = $(this).closest('form');

                Swal.fire({
                    icon: 'question',
                    title: '¿Continuar con la conversión?',
                    text:
                        'Se comprobarán los datos del cliente antes de abrir el formulario de reserva.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#093D77',
                    cancelButtonColor: '#6C7780',
                    reverseButtons: true
                }).then(function (resultado) {
                    if (resultado.isConfirmed) {
                        formulario[0].submit();
                    }
                });
            }
        );
    });
</script>

@endsection
