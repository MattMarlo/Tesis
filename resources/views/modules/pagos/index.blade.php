@extends('layouts.main')

@section('content')
<style>
    /* Estilos específicos para replicar el diseño de "Pagos" basado en la imagen */
    body {
        background-color: #12141d;
        color: #e2e8f0;
    }
    
    .page-title {
        color: #fff;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }
    
    .page-subtitle {
        color: #94a3b8;
        font-size: 0.9rem;
    }
    
    .btn-registrar {
        background-color: #3b82f6;
        color: white;
        border-radius: 8px;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border: none;
    }
    
    /* Stats Cards */
    .stat-card {
        background-color: #1e212b;
        border-radius: 12px;
        padding: 1.25rem;
        border: 1px solid #2d313f;
        border-top: 3px solid transparent;
        height: 100%;
    }
    .stat-card-total { border-top-color: #3b82f6; }
    .stat-card-cobrado { border-top-color: #10b981; }
    .stat-card-pendiente { border-top-color: #f59e0b; }
    .stat-card-sin-iniciar { border-top-color: #ef4444; }
    
    .stat-title {
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .stat-value.total { color: #3b82f6; }
    .stat-value.cobrado { color: #10b981; }
    .stat-value.pendiente { color: #f59e0b; }
    .stat-value.sin-iniciar { color: #ef4444; }
    
    .stat-desc {
        color: #64748b;
        font-size: 0.8rem;
    }
    
    /* Table Area */
    .table-container {
        background-color: #1e212b;
        border-radius: 12px;
        border: 1px solid #2d313f;
        overflow: hidden;
    }
    
    .table-dark-custom {
        width: 100%;
        color: #cbd5e1;
        border-collapse: collapse;
    }
    
    .table-dark-custom th {
        background-color: #1e212b;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #2d313f;
    }
    
    .table-dark-custom td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #2d313f;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    
    .table-dark-custom tr:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    /* Badges & Text Colors */
    .text-cobrado { color: #10b981 !important; font-weight: 600; }
    .text-pendiente { color: #ef4444 !important; font-weight: 600; }
    .text-parcial { color: #f59e0b !important; font-weight: 600; }
    
    .badge-status {
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .badge-status i { font-size: 0.5rem; }
    
    .bg-status-completado { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .bg-status-sinpago { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .bg-status-parcial { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    
    .badge-grupo {
        background-color: #4c1d95;
        color: #ddd6fe;
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        margin-right: 0.5rem;
    }
    
    .badge-lider {
        background-color: #5b21b6;
        color: #c4b5fd;
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        margin-left: 0.5rem;
    }
    
    .avatar-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #3b82f6;
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        margin-right: 0.5rem;
    }
    
    /* Acción Buttons */
    .btn-action {
        border-radius: 8px;
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
        font-weight: 500;
        background: transparent;
        border: 1px solid #334155;
        color: #cbd5e1;
        transition: all 0.2s;
    }
    .btn-action:hover {
        background-color: #334155;
        color: white;
    }
    
    .btn-action-cobrar {
        border-color: #047857;
        color: #10b981;
    }
    .btn-action-cobrar:hover {
        background-color: rgba(16, 185, 129, 0.1);
        color: #34d399;
    }
    
    /* Filters */
    .search-input {
        background-color: #1e212b;
        border: 1px solid #2d313f;
        color: #cbd5e1;
        border-radius: 8px;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        width: 100%;
        max-width: 400px;
    }
    .search-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: none;
        background-color: #1e212b;
        color: #fff;
    }
    .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
    }
    
    .filter-select {
        background-color: #1e212b;
        border: 1px solid #2d313f;
        color: #cbd5e1;
        border-radius: 8px;
        padding: 0.5rem 2rem 0.5rem 1rem;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
    }
    
    /* Desglose Grupal Panel */
    .desglose-panel {
        background-color: #161821;
        border-top: 1px solid #2d313f;
        display: none;
    }
    .desglose-panel.active {
        display: table-row;
    }
    .desglose-container {
        padding: 1.5rem !important;
    }
    .desglose-header {
        font-weight: 600;
        color: #f8fafc;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }
    
    /* Modal styles overrides for dark theme */
    .modal-content.dark-modal {
        background-color: #1e212b;
        color: #e2e8f0;
        border: 1px solid #2d313f;
    }
    .modal-content.dark-modal .modal-header {
        border-bottom: 1px solid #2d313f;
    }
    .modal-content.dark-modal .modal-footer {
        border-top: 1px solid #2d313f;
    }
    .dark-input {
        background-color: #12141d;
        border: 1px solid #2d313f;
        color: #e2e8f0;
    }
    .dark-input:focus {
        background-color: #12141d;
        color: white;
        border-color: #3b82f6;
        box-shadow: none;
    }
</style>

<div class="container-fluid py-4" style="max-width: 1400px;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title">Pagos</h1>
            <div class="page-subtitle">Control de transacciones y cobros</div>
        </div>
        <div>
            <button class="btn btn-registrar" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">
                + Registrar pago
            </button>
        </div>
    </div>

    @if (session('success') && !session('toast_sync'))
        <div class="alert alert-success bg-status-completado border-0">
            {{ session('success') }}
        </div>
    @endif

    @if($reservaFiltroId ?? null)
        <div class="alert border-0 mb-3" style="background:rgba(59,130,246,0.12);color:#93c5fd;border:1px solid rgba(59,130,246,0.35)!important;">
            Filtrando por reserva #{{ $reservaFiltroId }}.
            <a href="{{ route('pagos') }}" class="text-white text-decoration-underline ms-2">Quitar filtro</a>
        </div>
    @endif

    @if(session('toast_sync'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="toastSyncPagos" class="toast align-items-center text-bg-success border-0" role="alert" data-bs-autohide="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check2-circle me-1"></i> {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    @endif

    <!-- Cards de Resumen -->
    <div class="row g-4 mb-4">
        <!-- Card: total pagos -->
        <div class="col-md-3">
            <div class="stat-card stat-card-total">
                <div class="stat-title">TOTAL PAGOS</div>
                <div class="stat-value total">€{{ number_format($metricas['cobrado'], 0, ',', '.') }}</div>
                <div class="stat-desc">{{ $metricas['total_trx'] }} transacciones</div>
            </div>
        </div>
        <!-- Card: Cobrado -->
        <div class="col-md-3">
            <div class="stat-card stat-card-cobrado">
                <div class="stat-title">COBRADO</div>
                <div class="stat-value cobrado">€{{ number_format($metricas['cobrado'], 0, ',', '.') }}</div>
                <div class="stat-desc">Tasa de cobro {{ $metricas['tasa_cobro'] }}%</div>
            </div>
        </div>
        <!-- Card: Pendiente -->
        <div class="col-md-3">
            <div class="stat-card stat-card-pendiente">
                <div class="stat-title">PENDIENTE</div>
                <div class="stat-value pendiente">€{{ number_format($metricas['pendiente'], 0, ',', '.') }}</div>
                <div class="stat-desc">{{ $metricas['reservas_deuda'] }} reservas con deuda</div>
            </div>
        </div>
        <!-- Card: Sin Iniciar -->
        <div class="col-md-3">
            <div class="stat-card stat-card-sin-iniciar">
                <div class="stat-title">SIN INICIAR</div>
                <div class="stat-value sin-iniciar">€{{ number_format($metricas['sin_iniciar_monto'], 0, ',', '.') }}</div>
                <div class="stat-desc">
                    @if($metricas['reserva_critica'])
                        Reserva #{{ $metricas['reserva_critica'] }} crítica
                    @else
                        Sin reservas pendientes nuevas
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="position-relative w-100" style="max-width: 400px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="form-control search-input" id="searchPagos" placeholder="Buscar por cliente o reserva...">
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('pagos') }}" method="GET" class="d-flex gap-2" id="formFiltros">
                @if($reservaFiltroId ?? null)
                    <input type="hidden" name="reserva_id" value="{{ $reservaFiltroId }}">
                @endif
                <select name="estado" class="form-select filter-select" onchange="document.getElementById('formFiltros').submit()">
                    <option value="todos" {{ $filtros['estado'] == 'todos' ? 'selected' : '' }}>Todos los estados</option>
                    <option value="completado" {{ $filtros['estado'] == 'completado' ? 'selected' : '' }}>Completado</option>
                    <option value="parcial" {{ $filtros['estado'] == 'parcial' ? 'selected' : '' }}>Parcial</option>
                    <option value="sin pago" {{ $filtros['estado'] == 'sin pago' ? 'selected' : '' }}>Sin pago</option>
                </select>
                <select name="metodo" class="form-select filter-select" onchange="document.getElementById('formFiltros').submit()">
                    <option value="todos" {{ $filtros['metodo'] == 'todos' ? 'selected' : '' }}>Todos los métodos</option>
                    <option value="transferencia" {{ $filtros['metodo'] == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                    <option value="tarjeta" {{ $filtros['metodo'] == 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                    <option value="efectivo" {{ $filtros['metodo'] == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="table-container mb-4">
        <table class="table-dark-custom" id="tablaPagos">
            <thead>
                <tr>
                    <th>ID PAGO</th>
                    <th>RESERVA</th>
                    <th>CLIENTE / GRUPO</th>
                    <th>PAGADO</th>
                    <th>PENDIENTE</th>
                    <th>MÉTODO</th>
                    <th>FECHA</th>
                    <th>ESTADO</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservas as $reserva)
                <tr class="reserva-row">
                    <td>#P{{ $reserva['id_ultimo_pago'] ?? '-' }}</td>
                    <td>#{{ $reserva['reserva_id'] }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            @if($reserva['tipo'] == 'grupal')
                                <span class="badge-grupo">Grupo</span>
                            @else
                                @php
                                    // Generar iniciales
                                    $partes = explode(' ', $reserva['cliente_grupo']);
                                    $ini = strtoupper(substr($partes[0]??'C', 0, 1) . substr($partes[1]??'', 0, 1));
                                    $bg_color = '#' . substr(md5($reserva['cliente_grupo']), 0, 6);
                                @endphp
                                <span class="avatar-circle" style="background-color: {{ $bg_color }}">{{ $ini }}</span>
                            @endif
                            {{ $reserva['cliente_grupo'] }}
                        </div>
                    </td>
                    <td class="text-cobrado">€{{ number_format($reserva['pagado'], 0, ',', '.') }}</td>
                    <td class="{{ $reserva['pendiente'] > 0 ? 'text-pendiente' : '' }}">
                        €{{ number_format($reserva['pendiente'], 0, ',', '.') }}
                    </td>
                    <td>{{ $reserva['metodo'] }}</td>
                    <td>{{ $reserva['fecha_ultimo_pago'] }}</td>
                    <td>
                        @if($reserva['estado'] == 'Completado')
                            <span class="badge-status bg-status-completado"><i class="bi bi-circle-fill"></i> Completado</span>
                        @elseif($reserva['estado'] == 'Parcial')
                            <span class="badge-status bg-status-parcial"><i class="bi bi-circle-fill"></i> Parcial {{ $reserva['porcentaje'] }}%</span>
                        @else
                            <span class="badge-status bg-status-sinpago"><i class="bi bi-circle-fill"></i> Sin pago</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if(!empty($reserva['id_ultimo_pago']))
                            <button type="button" class="btn btn-action" onclick="abrirModalAuditoria({{ $reserva['id_ultimo_pago'] }}, {{ $reserva['reserva_id'] }})">Ver</button>
                        @else
                            <button type="button" class="btn btn-action" disabled title="Sin transacciones">Ver</button>
                        @endif

                        @if($reserva['tipo'] == 'grupal')
                            <button type="button" class="btn btn-action btn-desglose" data-id="{{ $reserva['reserva_id'] }}" data-nombre="{{ $reserva['cliente_grupo'] }}">Desglose</button>
                        @elseif($reserva['pendiente'] > 0)
                            <button type="button" class="btn btn-action btn-action-cobrar" onclick="abrirModalCobrar({{ $reserva['reserva_id'] }}, '{{ addslashes($reserva['cliente_grupo']) }}', {{ $reserva['pendiente'] }})">Cobrar</button>
                        @endif
                    </td>
                </tr>
                
                @if($reserva['tipo'] == 'grupal')
                <!-- Sub fila de desglose (oculta por defecto) -->
                <tr class="desglose-panel" id="desglose-{{ $reserva['reserva_id'] }}">
                    <td colspan="9" class="desglose-container">
                        <div class="desglose-header">
                            Desglose grupal &mdash; {{ $reserva['cliente_grupo'] }} (Reserva #{{ $reserva['reserva_id'] }})
                        </div>
                        <table class="table-dark-custom" style="background-color: transparent;">
                            <thead>
                                <tr>
                                    <th style="background-color: transparent;">INTEGRANTE</th>
                                    <th style="background-color: transparent;">ASIGNADO</th>
                                    <th style="background-color: transparent;">PAGADO</th>
                                    <th style="background-color: transparent;">PENDIENTE</th>
                                    <th style="background-color: transparent;">ESTADO</th>
                                    <th style="background-color: transparent;"></th>
                                </tr>
                            </thead>
                            <tbody id="body-desglose-{{ $reserva['reserva_id'] }}">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Cargando integrantes...</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
                
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Pago (Global / Individual / Grupal) -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('pagos.store') }}" method="POST" class="modal-content dark-modal">
            @csrf
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-secondary">ID Reserva</label>
                    <input type="number" name="reserva_id" id="modal_reserva_id" class="form-control dark-input" required @if($reservaFiltroId ?? null) value="{{ $reservaFiltroId }}" readonly @endif>
                </div>
                
                <input type="hidden" name="cliente_id" id="modal_cliente_id"> <!-- Solo si paga un integrante grupal -->
                
                <div class="mb-3">
                    <label class="form-label text-secondary">Cliente</label>
                    <input type="text" id="modal_cliente_nombre" class="form-control dark-input" disabled>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-secondary">Monto (€)</label>
                        <input type="number" step="0.01" name="monto_depositado" id="modal_monto" class="form-control dark-input" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-secondary">Método</label>
                        <select name="metodo_pago" class="form-select dark-input" required>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="efectivo">Efectivo</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary">Referencia (Opcional)</label>
                    <input type="text" name="referencia" class="form-control dark-input">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary text-white" style="background:#334155; border:none;" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-registrar">Procesar pago</button>
            </div>
        </form>
    </div>
</div>

{{-- Auditoría / recibo digital --}}
<div class="modal fade" id="modalAuditoriaPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dark-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Auditoría de transacción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body small">
                <p class="mb-2"><span class="text-secondary">ID pago:</span> <strong id="aud_id">—</strong></p>
                <p class="mb-2"><span class="text-secondary">Reserva:</span> <strong id="aud_reserva">—</strong></p>
                <p class="mb-2"><span class="text-secondary">Cliente:</span> <span id="aud_cliente">—</span></p>
                <p class="mb-2"><span class="text-secondary">Cobró:</span> <span id="aud_cobrador">—</span></p>
                <p class="mb-2"><span class="text-secondary">Método:</span> <span id="aud_metodo">—</span></p>
                <p class="mb-2"><span class="text-secondary">Referencia:</span> <span id="aud_ref">—</span></p>
                <p class="mb-0"><span class="text-secondary">Fecha ingreso:</span> <span id="aud_fecha" class="text-white">—</span></p>
                <p class="mt-3 mb-0"><span class="text-secondary">Monto:</span> <span class="text-cobrado fs-5" id="aud_monto">—</span></p>
            </div>
            <div class="modal-footer border-0 flex-wrap gap-2">
                <button type="button" class="btn btn-secondary text-white" style="background:#334155;border:none;" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn text-white" style="background:#3b82f6;" id="btn_abrir_editar_pago" onclick="abrirModalEditarDesdeAuditoria()">Editar pago</button>
                <button type="button" class="btn text-white" style="background:#ef4444;" id="btn_anular_este_pago" onclick="confirmarAnularPago()">Anular este pago</button>
                <button type="button" class="btn text-white" style="background:#ec4899;" id="btn_anular_otro_pago" onclick="abrirModalAnularOtroPago()">Anular otro pago</button>
            </div>
        </div>
    </div>
</div>

{{-- Editar pago --}}
<div class="modal fade" id="modalEditarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditarPago" method="POST" class="modal-content dark-modal">
            @csrf
            @method('PUT')
            <input type="hidden" name="reserva_id" id="edit_ctx_reserva_id" value="">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Editar pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-secondary">Monto (€)</label>
                    <input type="number" step="0.01" name="monto_depositado" id="edit_pago_monto" class="form-control dark-input" required min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary">Método</label>
                    <select name="metodo_pago" id="edit_pago_metodo" class="form-select dark-input" required>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label text-secondary">Referencia</label>
                    <input type="text" name="referencia" id="edit_pago_ref" class="form-control dark-input" maxlength="100">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary text-white" style="background:#334155;border:none;" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white" style="background:#10b981;">Guardar corrección</button>
            </div>
        </form>
    </div>
</div>

<form id="formAnularPago" method="POST" action="#" class="d-none">
    @csrf
    @method('DELETE')
    <input type="hidden" name="reserva_id" id="anular_ctx_reserva_id" value="">
</form>

{{-- Modal: Anular un pago diferente --}}
<div class="modal fade" id="modalAnularOtroPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dark-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Anular un pago diferente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label text-secondary fw-semibold mb-2">Seleccione qué pago desea anular:</label>
                <div id="lista_pagos_anular" style="max-height: 300px; overflow-y: auto; border: 1px solid #2d313f; border-radius: 8px; padding: 0;">
                    <div class="text-center text-muted py-4">Cargando pagos...</div>
                </div>
                <input type="hidden" id="pago_seleccionado_id" value="">
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary text-white" style="background:#334155;border:none;" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn text-white" style="background:#ef4444;" id="btn_confirmar_anular" onclick="confirmarAnularPagoSeleccionado()" disabled>Anular pago seleccionado</button>
            </div>
        </div>
    </div>
</div>

{{-- Editar integrante grupal --}}
<div class="modal fade" id="modalEditarIntegrante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dark-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Editar integrante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="int_reserva_id">
                <input type="hidden" id="int_cliente_id">
                <div class="mb-3">
                    <label class="form-label text-secondary">Nombres</label>
                    <input type="text" id="int_nombres" class="form-control dark-input" required maxlength="250">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary">Apellidos</label>
                    <input type="text" id="int_apellidos" class="form-control dark-input" required maxlength="250">
                </div>
                <div class="mb-0">
                    <label class="form-label text-secondary">Monto asignado (€)</label>
                    <input type="number" step="0.01" id="int_monto" class="form-control dark-input" required min="0">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary text-white" style="background:#334155;border:none;" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn text-white" style="background:#3b82f6;" onclick="guardarIntegrante()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const pagosBase = @json(url('/pagos'));
    const csrfPagos = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let pagoAuditoriaActual = null;
    let reservaCtxAuditoria = null;

    function abrirModalAuditoria(pagoId, reservaId) {
        reservaCtxAuditoria = reservaId;
        fetch(pagosBase + '/' + pagoId + '/auditoria', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error();
                pagoAuditoriaActual = data.data;
                document.getElementById('aud_id').textContent = '#' + data.data.id;
                document.getElementById('aud_reserva').textContent = '#' + data.data.reserva_id;
                document.getElementById('aud_cliente').textContent = data.data.cliente;
                document.getElementById('aud_cobrador').textContent = data.data.cobrador;
                document.getElementById('aud_metodo').textContent = data.data.metodo_pago;
                document.getElementById('aud_ref').textContent = data.data.referencia || '—';
                document.getElementById('aud_fecha').textContent = data.data.fecha_pago_fmt;
                document.getElementById('aud_monto').textContent = '€' + Number(data.data.monto).toLocaleString('es-ES');
                new bootstrap.Modal(document.getElementById('modalAuditoriaPago')).show();
            })
            .catch(() => alert('No se pudo cargar la auditoría del pago.'));
    }

    function abrirModalEditarDesdeAuditoria() {
        if (!pagoAuditoriaActual) return;
        bootstrap.Modal.getInstance(document.getElementById('modalAuditoriaPago'))?.hide();
        document.getElementById('formEditarPago').action = pagosBase + '/' + pagoAuditoriaActual.id;
        document.getElementById('edit_ctx_reserva_id').value = reservaCtxAuditoria || '';
        document.getElementById('edit_pago_monto').value = pagoAuditoriaActual.monto;
        const mv = (pagoAuditoriaActual.metodo_pago_val || '').toLowerCase();
        document.getElementById('edit_pago_metodo').value = mv || 'efectivo';
        document.getElementById('edit_pago_ref').value = pagoAuditoriaActual.referencia || '';
        new bootstrap.Modal(document.getElementById('modalEditarPago')).show();
    }

    function confirmarAnularPago() {
        if (!pagoAuditoriaActual) return;
        if (!confirm('¿Anular este pago? El monto se restará del balance y se actualizará la reserva.')) return;
        if (!confirm('Confirmación final: ¿anular el registro contable?')) return;
        const f = document.getElementById('formAnularPago');
        f.action = pagosBase + '/' + pagoAuditoriaActual.id;
        document.getElementById('anular_ctx_reserva_id').value = reservaCtxAuditoria || '';
        f.submit();
    }

    function abrirEditarIntegrante(reservaId, clienteId, nombreCompleto, asignado) {
        const partes = (nombreCompleto || '').trim().split(/\s+/);
        let nom = partes[0] || '';
        let ape = partes.length > 1 ? partes.slice(1).join(' ') : '';
        if (partes.length === 1 && nom) {
            ape = '-';
        }
        document.getElementById('int_reserva_id').value = reservaId;
        document.getElementById('int_cliente_id').value = clienteId;
        document.getElementById('int_nombres').value = nom;
        document.getElementById('int_apellidos').value = ape;
        document.getElementById('int_monto').value = asignado;
        new bootstrap.Modal(document.getElementById('modalEditarIntegrante')).show();
    }

    function guardarIntegrante() {
        fetch(pagosBase + '/integrante-grupal', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfPagos(),
            },
            body: JSON.stringify({
                reserva_id: document.getElementById('int_reserva_id').value,
                cliente_id: document.getElementById('int_cliente_id').value,
                nombres: document.getElementById('int_nombres').value,
                apellidos: document.getElementById('int_apellidos').value,
                monto_asignado: document.getElementById('int_monto').value,
            }),
        })
            .then(r => r.json())
            .then(j => {
                if (j.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarIntegrante'))?.hide();
                    window.location.reload();
                } else {
                    alert(j.message || 'No se pudo guardar');
                }
            })
            .catch(() => alert('Error de red'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(session('toast_sync'))
        const tp = document.getElementById('toastSyncPagos');
        if (tp && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            new bootstrap.Toast(tp).show();
        }
        @endif

        @if(!empty($abrirCobro) && !empty($reservaFiltroId))
        @php
            $filaCobro = collect($reservas)->firstWhere('reserva_id', (int) $reservaFiltroId);
        @endphp
        @if($filaCobro)
        abrirModalCobrar(
            {{ (int) $reservaFiltroId }},
            @json($filaCobro['cliente_grupo']),
            {{ $filaCobro['pendiente'] }}
        );
        @endif
        @endif

        // Buscador JS simple en tabla (opcional ya que hay backend, pero ayuda a UI fluida)
        document.getElementById('searchPagos').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.reserva-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                if(text.includes(term)) {
                    row.style.display = '';
                    // El panel de desglose también debe mostrarse si estaba activo? Mejor lo cerramos al filtrar
                    const desglose = row.nextElementSibling;
                    if(desglose && desglose.classList.contains('desglose-panel')) {
                        desglose.classList.remove('active');
                    }
                } else {
                    row.style.display = 'none';
                    const desglose = row.nextElementSibling;
                    if(desglose && desglose.classList.contains('desglose-panel')) {
                        desglose.style.display = 'none';
                    }
                }
            });
        });

        // Botones de Desglose
        const botonesDesglose = document.querySelectorAll('.btn-desglose');
        botonesDesglose.forEach(btn => {
            btn.addEventListener('click', function() {
                const reservaId = this.getAttribute('data-id');
                const panel = document.getElementById('desglose-' + reservaId);
                
                // Toggle
                if (panel.classList.contains('active')) {
                    panel.classList.remove('active');
                    this.textContent = 'Desglose';
                    this.classList.remove('bg-secondary');
                } else {
                    panel.classList.add('active');
                    this.textContent = 'Cerrar desglose';
                    this.classList.add('bg-secondary');
                    this.classList.add('text-white');
                    
                    // Cargar datos por fetch
                    fetch(`/pagos/grupo/${reservaId}`)
                        .then(res => res.json())
                        .then(data => {
                            const tbody = document.getElementById('body-desglose-' + reservaId);
                            if(data.success && data.data.length > 0) {
                                tbody.innerHTML = '';
                                data.data.forEach(intg => {
                                    
                                    // Generar iniciales
                                    const part = intg.nombre_completo.split(' ');
                                    const ini2 = (part[0]?part[0].charAt(0):'C') + (part[1]?part[1].charAt(0):'').toUpperCase();
                                    const bgColor2 = '#' + intg.nombre_completo.length + 'a23c2'; // mock color

                                    const pndColor = intg.pendiente > 0 ? 'text-pendiente' : '';
                                    
                                    let estadoBadge = '';
                                    if(intg.estado === 'Pagado') estadoBadge = '<span class="badge-status bg-status-completado"><i class="bi bi-circle-fill"></i> Pagado</span>';
                                    else if(intg.estado === 'Parcial') estadoBadge = '<span class="badge-status bg-status-parcial"><i class="bi bi-circle-fill"></i> Parcial</span>';
                                    else estadoBadge = '<span class="badge-status bg-status-sinpago"><i class="bi bi-circle-fill"></i> Sin pago</span>';

                                    let actionsBtn = '';
                                    const nomJs = JSON.stringify(intg.nombre_completo);
                                    if(intg.pendiente > 0) {
                                        actionsBtn = `<button type="button" class="btn btn-action btn-action-cobrar" onclick='abrirModalCobrar(${reservaId}, ${nomJs}, ${intg.pendiente}, ${intg.cliente_id})'>Cobrar</button>`;
                                    } else {
                                        actionsBtn = `<button type="button" class="btn btn-action" disabled>Recibo</button>`;
                                    }
                                    actionsBtn += ` <button type="button" class="btn btn-action" title="Corregir nombre o monto asignado" onclick='abrirEditarIntegrante(${reservaId}, ${intg.cliente_id}, ${nomJs}, ${intg.asignado})'><i class="bi bi-pencil"></i></button>`;

                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar-circle" style="background-color: ${bgColor2}">${ini2}</span>
                                                ${intg.nombre_completo}
                                                ${intg.es_lider ? '<span class="badge-lider">Líder</span>' : ''}
                                            </div>
                                        </td>
                                        <td>€${intg.asignado}</td>
                                        <td class="text-cobrado">€${intg.pagado}</td>
                                        <td class="${pndColor}">€${intg.pendiente}</td>
                                        <td>${estadoBadge}</td>
                                        <td class="text-end text-nowrap">${actionsBtn}</td>
                                    `;
                                    tbody.appendChild(tr);
                                });
                            } else {
                                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron integrantes or no se asignaron montos</td></tr>';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            document.getElementById('body-desglose-' + reservaId).innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar datos.</td></tr>';
                        });
                }
            });
        });
    });

    function abrirModalCobrar(reservaId, clienteNombre, pendiente, clienteId = '') {
        document.getElementById('modal_reserva_id').value = reservaId;
        document.getElementById('modal_cliente_nombre').value = clienteNombre;
        document.getElementById('modal_monto').value = pendiente;
        document.getElementById('modal_cliente_id').value = clienteId;

        var myModal = new bootstrap.Modal(document.getElementById('modalRegistrarPago'));
        myModal.show();
    }

    /**
     * NUEVO: Abre modal para seleccionar y anular un pago diferente
     * Carga todos los pagos de la reserva en una lista seleccionable
     */
    function abrirModalAnularOtroPago() {
        if (!reservaCtxAuditoria) {
            alert('No hay contexto de reserva. Por favor, intente nuevamente.');
            return;
        }

        // Cerrar modal actual
        bootstrap.Modal.getInstance(document.getElementById('modalAuditoriaPago'))?.hide();

        // Limpiar estado previo
        document.getElementById('pago_seleccionado_id').value = '';
        document.getElementById('btn_confirmar_anular').disabled = true;
        document.getElementById('lista_pagos_anular').innerHTML = '<div class="text-center text-muted py-4">Cargando pagos...</div>';

        // Cargar todos los pagos de la reserva
        fetch(pagosBase + '/reserva/' + reservaCtxAuditoria + '/pagos-lista', {
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.data || data.data.length === 0) {
                    document.getElementById('lista_pagos_anular').innerHTML = 
                        '<div class="text-center text-muted py-4">No hay pagos registrados en esta reserva.</div>';
                    return;
                }

                const listHTML = data.data.map((pago, idx) => `
                    <div style="padding:12px;border-bottom:1px solid #2d313f;cursor:pointer;transition:all 0.2s;" 
                         class="pago-item" 
                         data-pago-id="${pago.id}"
                         onclick="seleccionarPago(${pago.id}, this)">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="text-white d-block">${pago.cliente}</strong>
                                <small class="text-muted">ID: #${pago.id} • ${pago.metodo_pago} • ${pago.fecha_pago_fmt}</small>
                            </div>
                            <span class="text-cobrado fw-bold" style="font-size:1.1rem;">€${Number(pago.monto).toFixed(2)}</span>
                        </div>
                        ${pago.referencia && pago.referencia !== '—' ? '<small class="text-muted d-block mt-1">Ref: ' + pago.referencia + '</small>' : ''}
                    </div>
                `).join('');

                document.getElementById('lista_pagos_anular').innerHTML = listHTML;
            })
            .catch(err => {
                console.error(err);
                document.getElementById('lista_pagos_anular').innerHTML = 
                    '<div class="text-center text-danger py-4">Error al cargar los pagos.</div>';
            });

        // Mostrar modal
        new bootstrap.Modal(document.getElementById('modalAnularOtroPago')).show();
    }

    /**
     * NUEVO: Selecciona un pago de la lista y lo habilita para anular
     */
    function seleccionarPago(pagoId, element) {
        // Limpiar selección anterior
        document.querySelectorAll('.pago-item').forEach(item => {
            item.style.backgroundColor = 'transparent';
            item.style.borderLeft = '4px solid transparent';
        });

        // Marcar como seleccionado
        element.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
        element.style.borderLeft = '4px solid #ef4444';

        // Guardar ID y habilitar botón
        document.getElementById('pago_seleccionado_id').value = pagoId;
        document.getElementById('btn_confirmar_anular').disabled = false;
    }

    /**
     * NUEVO: Confirma la anulación del pago seleccionado
     */
    function confirmarAnularPagoSeleccionado() {
        const pagoId = document.getElementById('pago_seleccionado_id').value;
        
        if (!pagoId) {
            alert('Por favor, seleccione un pago para anular.');
            return;
        }

        if (!confirm('¿Está seguro de que desea ANULAR este pago?\n\nEl monto se restará del balance de la reserva.')) {
            return;
        }

        if (!confirm('⚠️ Confirmación final: ¿Anular el registro contable?')) {
            return;
        }

        // Proceder con la anulación
        const f = document.getElementById('formAnularPago');
        f.action = pagosBase + '/' + pagoId;
        document.getElementById('anular_ctx_reserva_id').value = reservaCtxAuditoria || '';
        
        // Mostrar mensajede progreso
        document.getElementById('btn_confirmar_anular').disabled = true;
        document.getElementById('btn_confirmar_anular').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Anulando...';

        // Enviar formulario
        f.submit();
    }
</script>
@endsection
