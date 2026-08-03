@extends('layouts.main')

@section('title', 'Devoluciones')

@section('content')
<link rel="stylesheet" href="{{ asset('css/devoluciones.css') }}">

<main class="pagina-devoluciones">
    @if ($errors->any())
        <div class="alerta-devolucion alerta-error">
            <strong>No se pudo registrar la devolución.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="devoluciones-encabezado">
        <div>
            <span>GESTIÓN FINANCIERA</span>
            <h1>Devoluciones</h1>
            <p>Registra reembolsos sin perder la trazabilidad de pagos y reservas.</p>
        </div>
        <button class="btn-principal" data-bs-toggle="modal" data-bs-target="#modalNuevaDevolucion">
            <i class="bi bi-plus-lg"></i> Nueva devolución
        </button>
    </header>

    <section class="metricas-devoluciones">
        <article><span>Total devuelto</span><strong>USD {{ number_format($totalProcesado, 2, '.', ',') }}</strong></article>
        <article><span>Operaciones</span><strong>{{ $devoluciones->total() }}</strong></article>
        <article><span>Pagos reembolsables</span><strong>{{ $pagos->count() }}</strong></article>
    </section>

    <form class="filtros-devoluciones" method="GET">
        <input name="buscar" value="{{ $buscar }}" placeholder="Buscar reserva o cliente">
        <button type="submit"><i class="bi bi-search"></i> Buscar</button>
        @if($buscar)<a href="{{ route('devoluciones.index') }}">Limpiar</a>@endif
    </form>

    <section class="tabla-contenedor">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Reserva / Cliente</th><th>Pago</th><th>Método</th><th>Motivo</th><th>Monto</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse($devoluciones as $devolucion)
                    <tr>
                        <td>{{ $devolucion->fecha_devolucion?->format('d/m/Y H:i') }}</td>
                        <td><strong>{{ $devolucion->reserva?->codigo_reserva }}</strong><small>{{ $devolucion->cliente?->nombre_completo }}</small></td>
                        <td>#{{ $devolucion->pago_id }}</td>
                        <td>{{ ucfirst($devolucion->metodo) }}<small>{{ $devolucion->referencia ?: 'Sin referencia' }}</small></td>
                        <td class="motivo"><strong>{{ ucfirst(str_replace('_', ' ', $devolucion->tipo)) }}</strong><small>{{ $devolucion->motivo }}</small></td>
                        <td><strong>USD {{ number_format((float)$devolucion->monto, 2, '.', ',') }}</strong></td>
                        <td><span class="estado estado-{{ $devolucion->estado }}">{{ ucfirst($devolucion->estado) }}</span></td>
                        <td>
                            @if(!$devolucion->estaAnulada())
                            <button class="btn-anular" data-bs-toggle="modal" data-bs-target="#modalAnularDevolucion" data-id="{{ $devolucion->id }}">Anular</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="sin-registros">No hay devoluciones registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="paginacion">{{ $devoluciones->links() }}</div>
    </section>
</main>

<div class="modal fade" id="modalNuevaDevolucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('devoluciones.store') }}">@csrf
        <div class="modal-header"><div><small>NUEVO MOVIMIENTO</small><h2>Registrar devolución</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body campos-devolucion">
            <label>Pago original *</label>
            <select name="pago_id" id="pagoDevolucion" required><option value="">Selecciona un pago</option>@foreach($pagos as $pago)@php($disponible=round((float)$pago->monto_depositado-(float)$pago->total_devuelto,2))<option value="{{ $pago->id }}" data-disponible="{{ number_format($disponible,2,'.','') }}" @selected(old('pago_id')==$pago->id)>#{{ $pago->id }} · {{ $pago->reserva?->codigo_reserva }} · {{ $pago->cliente?->nombre_completo }} · Disponible USD {{ number_format($disponible,2,'.',',') }}</option>@endforeach</select>
            <label>Monto *</label><input type="number" name="monto" value="{{ old('monto') }}" min="0.01" step="0.01" required>
            <label>Método *</label><select name="metodo" id="metodoDevolucion" required><option value="efectivo" @selected(old('metodo')==='efectivo')>Efectivo</option><option value="transferencia" @selected(old('metodo')==='transferencia')>Transferencia</option><option value="tarjeta" @selected(old('metodo')==='tarjeta')>Tarjeta</option><option value="otro" @selected(old('metodo')==='otro')>Otro</option></select>
            <label>Referencia</label><input type="text" name="referencia" value="{{ old('referencia') }}" maxlength="100" placeholder="Comprobante o transacción">
            <label>Tipo de devolución *</label><select name="tipo" required><option value="">Selecciona el motivo financiero</option><option value="cancelacion" @selected(old('tipo')==='cancelacion')>Cancelación de reserva</option><option value="correccion" @selected(old('tipo')==='correccion')>Corrección de cobro</option><option value="pago_duplicado" @selected(old('tipo')==='pago_duplicado')>Pago duplicado</option><option value="reduccion_servicios" @selected(old('tipo')==='reduccion_servicios')>Reducción de viajeros o servicios</option><option value="comercial" @selected(old('tipo')==='comercial')>Reembolso comercial</option><option value="otro" @selected(old('tipo')==='otro')>Otro</option></select>
            <label>Motivo *</label><textarea name="motivo" minlength="10" maxlength="1000" rows="4" required placeholder="Explica la causa de la devolución">{{ old('motivo') }}</textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn-secundario" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn-principal">Registrar devolución</button></div>
    </form></div>
</div>

<div class="modal fade" id="modalAnularDevolucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form id="formAnularDevolucion" class="modal-content" method="POST">@csrf @method('PATCH')
        <div class="modal-header"><h2>Anular devolución</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body campos-devolucion"><p>El registro permanecerá visible para auditoría.</p><label>Motivo *</label><textarea name="motivo_anulacion" minlength="10" maxlength="500" rows="4" required></textarea></div>
        <div class="modal-footer"><button type="button" class="btn-secundario" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn-peligro">Sí, anular</button></div>
    </form></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectorPago = document.getElementById('pagoDevolucion');
    const campoMonto = document.querySelector('#modalNuevaDevolucion [name="monto"]');

    function actualizarMontoMaximo() {
        const opcion = selectorPago.options[selectorPago.selectedIndex];
        const disponible = opcion?.dataset.disponible || '';

        if (disponible) {
            campoMonto.max = disponible;
        } else {
            campoMonto.removeAttribute('max');
        }
    }

    selectorPago.addEventListener('change', actualizarMontoMaximo);
    actualizarMontoMaximo();

    document.querySelectorAll('.btn-anular').forEach(function (boton) {
        boton.addEventListener('click', function () {
            document.getElementById('formAnularDevolucion').action =
                @json(url('/devoluciones')) + '/' + boton.dataset.id + '/anular';
        });
    });

    @if ($errors->any())
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalNuevaDevolucion')
        ).show();
    @endif
});
</script>
@endsection
