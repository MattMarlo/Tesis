@extends('layouts.main')

@section('titulo', 'Editar cliente')

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/clientes-formulario.css') }}"
>

<main id="main" class="main pagina-formulario-clientes">
    <div class="encabezado-formulario">
        <div>
            <span class="nombre-modulo">Clientes</span>

            <h1>Editar cliente</h1>

            <p>
                Actualiza la información de
                {{ $cliente->nombre_completo }}.
            </p>
        </div>

        <a
            href="{{ route('clientes') }}"
            class="volver-listado"
        >
            <i class="bi bi-arrow-left"></i>
            Volver a clientes
        </a>
    </div>

    @include('modules.clientes.partials.formulario')
</main>

<script>
    window.configuracionFormularioCliente = {
        modo: 'editar',
        errores: @json($errors->toArray()),
        mensajeError: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/clientes-formulario.js') }}"></script>
@endsection