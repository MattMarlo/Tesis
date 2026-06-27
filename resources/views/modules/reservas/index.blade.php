@extends('layouts.main')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Gestión de Reservas</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('reservas_individual.create') }}" class="btn fw-bold text-white" style="background-color:#3b82f6;border:none;">
                <i class="bi bi-plus-lg"></i> Reserva Individual
            </a>
            <a href="{{ route('reservas_grupal.create') }}" class="btn fw-bold text-white" style="background-color:#10b981;border:none;">
                <i class="bi bi-people"></i> Reserva Grupal
            </a>
        </div>
    </div>

    @if (session('success') && !session('toast_sync'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('toast_sync'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="toastSync" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4500">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check2-circle me-1"></i> {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Cliente Titular</th>
                            <th>Destino</th>
                            <th>Tipo Viaje</th>
                            <th>Fecha Viaje</th>
                            <th>Precio Total</th>
                            <th>Estado Reserva</th>
                            <th>Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservas as $res)
                        <tr data-reserva-id="{{ $res->id }}">
                            <td class="fw-bold">{{ $res->id }}</td>
                            <td class="fw-bold">{{ $res->codigo_reserva }}</td>
                            @if ($res->tipo=='grupal')
                                <td>{{ $res->nombre_grupo }} </td>
                            @else
                                <td>{{ $res->nombres }} {{ $res->apellidos }}</td>
                            @endif
                            
                            <td><span class="badge bg-secondary">{{ $res->pais }}</span></td>
                            <td><span class="text-capitalize">{{ $res->tipo }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($res->fecha_viaje)->format('d/m/Y') }}</td>
                            <td class="text-success fw-bold precio-total">€{{ number_format($res->precio_total_viaje, 2) }}</td>
                            <td class="estado-reserva"><span class="text-capitalize">{{ $res->estado }}</span></td>
                            <td class="estado-pago">
                                @if($res->estado_pago == 'pagado')
                                    <span class="badge bg-success">Completado</span>
                                @elseif($res->estado_pago == 'parcial')
                                    <span class="badge bg-warning text-dark">Parcial: {{ $res->total_depositado }}</span>
                                @else
                                    <span class="badge bg-danger">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <button type="button"
                                        class="btn btn-sm px-3 rounded-pill text-white fw-semibold"
                                        style="background-color:#3b82f6;border:1px solid #2563eb;"
                                        onclick="abrirModalDetalleReserva({{ $res->id }})">
                                        Ver Detalle
                                    </button>

                                    @if($res->tipo !== 'grupal' && $res->estado_pago !== 'pagado')
                                        @php
                                            $pendienteRes   = max(0, $res->precio_total_viaje - $res->total_depositado);
                                            $nombreCliente  = $res->nombres . ' ' . $res->apellidos;
                                        @endphp
                                        <button
                                            type="button"
                                            class="btn btn-sm px-3 rounded-pill text-success fw-bold"
                                            style="background-color: rgba(16, 185, 129, 0.12); border: 1px solid #10b981;"
                                            onclick="abrirModalCobrarReserva({{ $res->id }}, '{{ addslashes($nombreCliente) }}', {{ $pendienteRes }})">
                                            Cobrar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No hay reservas registradas en el sistema.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>

    <div class="mt-3">
        {{ $reservas->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- Modal detalle reserva (ERP) --}}
<div class="modal fade" id="modalDetalleReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="detalle_codigo">—</h5>
                    <div class="small text-muted" id="detalle_fecha_creacion"></div>
                    <span id="detalle_badge_estado" class="badge mt-2"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3">Cliente titular</h6>
                        <div id="vista_lectura_titular">
                            <p class="mb-1"><strong id="detalle_titular_nombre">—</strong></p>
                            <p class="mb-1 small text-muted" id="detalle_titular_email">—</p>
                            <p class="mb-0 small text-muted" id="detalle_titular_tel">—</p>
                            <div class="mt-2" id="detalle_titular_arch"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3">Detalle del viaje</h6>
                        <div id="vista_lectura_viaje">
                            <p class="mb-1"><span class="badge bg-dark" id="detalle_destino_badge">—</span></p>
                            <p class="mb-1 small"><strong>Reserva:</strong> <span id="detalle_fecha_reserva_txt">—</span></p>
                            <p class="mb-1 small"><strong>Viaje:</strong> <span id="detalle_fecha_viaje_txt">—</span></p>
                            <p class="mb-1 small"><strong>Precio total:</strong> <span class="text-success fw-bold" id="detalle_precio_txt">—</span></p>
                            <p class="mb-0 small text-muted" id="detalle_itinerario">—</p>
                            <p class="mb-0 small mt-2" id="detalle_grupo_linea" style="display:none;"><span class="badge" style="background:#4c1d95;">Grupo</span> <span id="detalle_grupo_nombre"></span></p>
                        </div>
                        <div id="vista_edicion_viaje" class="d-none">
                            <input type="hidden" id="edit_reserva_id">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Destino</label>
                                <select id="edit_destino_id" class="form-select form-select-sm"></select>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label class="form-label small fw-semibold">Fecha reserva</label>
                                    <input type="date" id="edit_fecha_reserva" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="form-label small fw-semibold">Fecha viaje</label>
                                    <input type="date" id="edit_fecha_viaje" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Precio total($)</label>
                                <input type="number" step="0.01" id="edit_precio_total" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Estado reserva</label>
                                <select id="edit_estado" class="form-select form-select-sm">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm text-white" style="background:#3b82f6;" onclick="guardarEdicionReserva()">Guardar cambios</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cancelarEdicionReserva()">Cancelar edición</button>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                {{-- Integrantes del grupo si es grupal --}}
                <div id="seccion_integrantes" style="display:none;" class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3">Integrantes del grupo</h6>
                        
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="abrirGestionIntegrantes()">
                            <i class="bi bi-gear-fill me-1"></i> Gestionar
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th class="text-end">Asignado</th>
                                    <th class="text-end">Pagado</th>
                                    <th class="text-end">Deuda</th>
                                    <th class="text-center">Rol</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_integrantes">
                            </tbody>
                        </table>
                    </div>
                    <hr class="my-3">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-secondary text-uppercase small">Registrar pago</h6>
                    <span class="small text-muted">Deuda: <strong id="detalle_saldo_pendiente" class="text-danger">—</strong></span>
                </div>
                <form action="{{ route('pagos.store') }}" method="POST" class="border rounded p-3 bg-light" id="formRegistroPago" >
                    @csrf
                    <input type="hidden" name="redirect_after" value="reservas">
                    <input type="hidden" name="reserva_id" id="registro_reserva_id">
                    <input type="hidden" name="cliente_id" id="registro_cliente_id">
                    <div id="campo_cliente_grupal" style="display:none;" class="mb-2">
                        <label class="form-label small fw-semibold">Cliente a cobrar</label>
                        <select name="cliente_id" id="registro_cliente_grupal" class="form-select form-select-sm" onchange="actualizarMontoPendienteGrupal()">
                            <option value="">Seleccionar cliente...</option>
                        </select>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Monto($)</label>
                            <input type="number" step="0.01" name="monto_depositado" id="registro_monto" class="form-control form-control-sm" required min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Método</label>
                            <select name="metodo_pago" class="form-select form-select-sm" required>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="efectivo">Efectivo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-0">Referencia</label>
                            <input type="text" name="referencia" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm text-white w-100 fw-bold" style="background:#10b981;">+ Registrar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn text-white" style="background:#3b82f6;" id="btn_editar_logistica" onclick="activarEdicionReserva()">Editar</button>
                <button type="button" class="btn btn-outline-danger" id="btn_eliminar_reserva" onclick="confirmarEliminarReserva()">Eliminar</button>
                <a href="#" class="btn text-white fw-bold" id="btn_gestionar_pago" style="background:#10b981;">Gestionar pago</a>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Cobro de Reservas (atajo) --}}
<div class="modal fade" id="modalCobrarReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('pagos.store') }}" method="POST" class="modal-content" id="formCobroRapido">
            @csrf
            <input type="hidden" name="redirect_after" value="reservas">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold">ID Reserva</label>
                    <input type="number" name="reserva_id" id="cobrar_reserva_id" class="form-control" readonly>
                </div>

                <input type="hidden" name="cliente_id" id="cobrar_cliente_id">
                <!--cambio aqui -->
                <input type="hidden" id="cobrar_monto_maximo" value="">

                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold">Cliente</label>
                    <input type="text" id="cobrar_cliente_nombre" class="form-control" disabled>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-secondary fw-semibold">Monto($)</label>
                        <input type="number" step="0.01" min="0.01" name="monto_depositado" id="cobrar_monto" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-secondary fw-semibold">Método</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="efectivo">Efectivo</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold">Referencia (Opcional)</label>
                    <input type="text" name="referencia" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn fw-bold text-white" style="background:#10b981;">Procesar pago</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalGestionIntegrantes" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-people-fill me-2"></i>Gestionar Integrantes
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- BUSCAR CLIENTE -->
                <label class="form-label small fw-bold text-secondary">
                    Buscar por cédula
                </label>

                <div class="input-group input-group-sm mb-3">
                    <input 
                        type="text" 
                        id="input_buscar_cedula"
                        class="form-control" 
                        placeholder="Ej: 1801234567">
                    
                    <button class="btn btn-primary" type="button" onclick="buscarCliente()">
                        Buscar
                    </button>
                </div>

                <!-- RESULTADO -->
                <div id="resultado_busqueda" class="mb-3"></div>

                <hr>

                <!--  LISTA ACTUAL -->
                <label class="form-label small fw-bold text-secondary">
                    Lista actual
                </label>

                <div id="lista_pasajeros_editar"></div>

            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>

                <button type="button" class="btn btn-sm btn-success" onclick="guardarCambiosIntegrantes()">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    const detalleUrlBase = @json(url('/reservas'));
    const clientesBuscarUrl = @json(route('clientes.buscarDocumento'));
    let datosDetalleActual = null;
    let integrantesEliminados=[];
    let nuevosIntegrantes=[];
    let nuevoLiderId = null;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    function badgeEstadoReserva(estado) {
        const e = (estado || '').toLowerCase();
        if (e === 'confirmada') return '<span class="badge bg-success">Confirmada</span>';
        if (e === 'cancelada') return '<span class="badge" style="background:#ef4444;">Cancelada</span>';
        return '<span class="badge bg-warning text-dark">Pendiente</span>';
    }

    function actualizarFilaReserva(detalle) {
        const fila = document.querySelector('tr[data-reserva-id="' + detalle.id + '"]');
        if (!fila) return;

        const precioCelda = fila.querySelector('.precio-total');
        const estadoCelda = fila.querySelector('.estado-reserva span');
        const pagoCelda = fila.querySelector('.estado-pago');

        if (precioCelda && detalle.precio_total_viaje !== undefined) {
            precioCelda.textContent = '€' + Number(detalle.precio_total_viaje).toFixed(2);
        }

        if (estadoCelda && detalle.estado !== undefined) {
            estadoCelda.textContent = (detalle.estado || '').charAt(0).toUpperCase() + (detalle.estado || '').slice(1);
        }

        if (pagoCelda && detalle.estado_pago !== undefined) {
            let html = '';
            if (detalle.estado_pago === 'pagado') {
                html = '<span class="badge bg-success">Completado</span>';
            } else if (detalle.estado_pago === 'parcial') {
                const total = Number(detalle.total_depositado || 0).toFixed(2);
                html = '<span class="badge bg-warning text-dark">Parcial: ' + total + '</span>';
            } else {
                html = '<span class="badge bg-danger">Pendiente</span>';
            }
            pagoCelda.innerHTML = html;
        }
    }

    function abrirModalDetalleReserva(id) {
        fetch(detalleUrlBase + '/' + id + '/detalle', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(json => {
                if (!json.success) throw new Error('Error al cargar');
                datosDetalleActual = json.data;
                poblarModalDetalle(json.data);
                const m = new bootstrap.Modal(document.getElementById('modalDetalleReserva'));
                m.show();
            })
            .catch(() => alert('No se pudo cargar el detalle de la reserva.'));
    }

    function poblarModalDetalle(d) {
        document.getElementById('detalle_codigo').textContent = d.codigo_reserva;
        document.getElementById('detalle_fecha_creacion').textContent = 'Creada: ' + (d.fecha_creacion || '—');
        document.getElementById('detalle_badge_estado').innerHTML = badgeEstadoReserva(d.estado);

        document.getElementById('detalle_titular_nombre').textContent = d.titular.nombre_completo || '—';
        document.getElementById('detalle_titular_email').textContent = d.titular.email || '';
        document.getElementById('detalle_titular_tel').textContent = d.titular.telefono || '';
        // Mostrar archivo (con botón)
        const archContainer = document.getElementById('detalle_titular_arch');
        if (d.titular.archivo) {
            const url = '/storage/' + d.titular.archivo;
            archContainer.innerHTML = `
                <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold" 
                style="border-color: #0d6efd; background-color: #f0f7ff; transition: all 0.2s;"
                onmouseover="this.style.backgroundColor='#0d6efd'; this.style.color='white';" 
                onmouseout="this.style.backgroundColor='#f0f7ff'; this.style.color='#0d6efd';">
                    <i class="bi bi-file-earmark-text me-1"></i> Ver documento
                </a>
            `;
        } else {
            archContainer.innerHTML = `<span class="text-muted small">Sin documento</span>`;
        }

        document.getElementById('detalle_destino_badge').textContent = d.destino.pais || '—';
        function fmtFecha(v) {
            if (!v) return '—';
            const s = String(v).substring(0, 10);
            const p = s.split('-');
            if (p.length === 3) return p[2] + '/' + p[1] + '/' + p[0];
            return v;
        }
        document.getElementById('detalle_fecha_reserva_txt').textContent = fmtFecha(d.fecha_reserva);
        document.getElementById('detalle_fecha_viaje_txt').textContent = fmtFecha(d.fecha_viaje);
        document.getElementById('detalle_precio_txt').textContent = '€' + Number(d.precio_total_viaje).toFixed(2);
        document.getElementById('detalle_itinerario').textContent = d.itinerario_resumen || '';

        // Si el detalle se actualiza (edición rápida), sincronizar la fila correspondiente de la tabla
        actualizarFilaReserva(d);

        const gl = document.getElementById('detalle_grupo_linea');
        if (d.tipo === 'grupal' && d.grupo_nombre) {
            gl.style.display = 'block';
            document.getElementById('detalle_grupo_nombre').textContent = d.grupo_nombre;
        } else {
            gl.style.display = 'none';
        }

        // Mostrar integrantes si es grupal
        const seccionIntegrantes = document.getElementById('seccion_integrantes');
        const tablaIntegrantes = document.getElementById('tabla_integrantes');
        const campClienteGrupal = document.getElementById('campo_cliente_grupal');
        
        if (d.tipo === 'grupal' && d.integrantes && d.integrantes.length > 0) {
            seccionIntegrantes.style.display = 'block';
            tablaIntegrantes.innerHTML = '';
            
            const selectClientes = document.getElementById('registro_cliente_grupal');
            selectClientes.innerHTML = '<option value="">Seleccionar cliente...</option>';
            
            d.integrantes.forEach(integrante => {
                // Agregar fila a tabla
                const fila = document.createElement('tr');
                const nombreCompleto = (integrante.nombres + ' ' + integrante.apellidos).trim();
                fila.innerHTML = `
                    <td class="editable-cell align-middle" onclick="activarEdicionRapida(this)">
                        <div class="d-flex align-items-center">
                            <span class="text-content fw-bold">${integrante.nombres}</span>
                            <i class="bi bi-pencil-fill edit-icon ms-auto" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </div>
                        <input type="text" class="form-control form-control-sm d-none input-edit" 
                            value="${integrante.nombres}" 
                            onblur="finalizarEdicionRapida(this, 'nombres', ${integrante.id})">
                    </td>

                    <td class="editable-cell align-middle" onclick="activarEdicionRapida(this)">
                        <div class="d-flex align-items-center">
                            <span class="text-content">${integrante.apellidos}</span>
                            <i class="bi bi-pencil-fill edit-icon ms-auto" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </div>
                        <input type="text" class="form-control form-control-sm d-none input-edit" 
                            value="${integrante.apellidos}" 
                            onblur="finalizarEdicionRapida(this, 'apellidos', ${integrante.id})">
                    </td>
                    <td class="editable-cell align-middle" onclick="activarEdicionRapida(this)">
                        <div class="d-flex align-items-center">
                            <span class="text-content">${integrante.email}</span>
                            <i class="bi bi-pencil-fill edit-icon ms-auto" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </div>
                        <input type="text" class="form-control form-control-sm d-none input-edit" 
                            value="${integrante.email}" 
                            onblur="finalizarEdicionRapida(this, 'email', ${integrante.id})">
                    </td>
                    <td class="editable-cell align-middle" onclick="activarEdicionRapida(this)">
                        <div class="d-flex align-items-center">
                            <span class="text-content">${integrante.telefono}</span>
                            <i class="bi bi-pencil-fill edit-icon ms-auto" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </div>
                        <input type="text" class="form-control form-control-sm d-none input-edit" 
                            value="${integrante.telefono}" 
                            onblur="finalizarEdicionRapida(this, 'telefono', ${integrante.id})">
                    </td>
                    <td class="editable-cell align-middle" onclick="activarEdicionRapida(this)">
                        <div class="d-flex align-items-center">
                            <span class="text-content">€${Number(integrante.monto_asignado).toFixed(2)}</span>
                            <i class="bi bi-pencil-fill edit-icon ms-auto" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </div>
                        <input type="text" class="form-control form-control-sm d-none input-edit" 
                            value="${Number(integrante.monto_asignado).toFixed(2)}" 
                            onblur="finalizarEdicionRapida(this, 'monto_asignado', ${integrante.id})">
                    </td>
                    
                    <td class="text-end">€${Number(integrante.pagado).toFixed(2)}</td>
                    <td class="text-end"><span class="badge ${integrante.deuda > 0 ? 'bg-danger' : 'bg-success'}">€${Number(integrante.deuda).toFixed(2)}</span></td>
                    <td class="text-center"><small>${integrante.es_lider ? '<span class="badge bg-dark">Líder</span>' : '—'}</small></td>
                `;
                tablaIntegrantes.appendChild(fila);
                
                // Agregar opción al select solo si tiene deuda
                if (integrante.deuda > 0) {
                    const option = document.createElement('option');
                    option.value = integrante.id;
                    option.textContent = nombreCompleto + ' (Deuda: €' + Number(integrante.deuda).toFixed(2) + ')';
                    selectClientes.appendChild(option);
                }
            });
            
            campClienteGrupal.style.display = 'block';
        } else {
            seccionIntegrantes.style.display = 'none';
            campClienteGrupal.style.display = 'none';
        }

        document.getElementById('edit_reserva_id').value = d.id;
        document.getElementById('registro_reserva_id').value = d.id;
        document.getElementById('registro_cliente_id').value = d.cliente_id || '';

        const pend = Math.max(0, Number(d.precio_total_viaje) - Number(d.total_depositado || 0));
        document.getElementById('detalle_saldo_pendiente').textContent = '$' + pend.toFixed(2);
        document.getElementById('registro_monto').value = pend > 0 ? pend.toFixed(2) : '';

        const sel = document.getElementById('edit_destino_id');
        sel.innerHTML = '';
        (d.destinos_opciones || []).forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.pais;
            if (d.destino && String(o.id) === String(d.destino.id)) opt.selected = true;
            sel.appendChild(opt);
        });

        document.getElementById('edit_fecha_reserva').value = (d.fecha_reserva || '').substring(0, 10);
        document.getElementById('edit_fecha_viaje').value = (d.fecha_viaje || '').substring(0, 10);
        document.getElementById('edit_precio_total').value = d.precio_total_viaje;
        document.getElementById('edit_estado').value = d.estado || 'pendiente';

        document.getElementById('vista_edicion_viaje').classList.add('d-none');
        document.getElementById('vista_lectura_viaje').classList.remove('d-none');
        document.getElementById('btn_editar_logistica').classList.remove('d-none');

        // Botón gestionar pago siempre visible, actualizar enlace
        const btnPago = document.getElementById('btn_gestionar_pago');
        //btnPago.href = @json(url('/pagos')) + '?reserva_id=' + d.id + '&abrir_cobro=1';
        btnPago.href = @json(url('/pagos')) + '?reserva_id=' + d.id ;

        // Lógica de negocio para eliminar:
        // - NO se puede eliminar si tiene pagos
        // - NO se puede eliminar si está confirmada
        // - SÍ se puede eliminar si está pendiente o cancelada SIN pagos
        const btnDel = document.getElementById('btn_eliminar_reserva');
        const estadoConfirmada = (d.estado || '').toLowerCase() === 'confirmada';
        const tienePagos = d.pagos_activos > 0;
        
        if (tienePagos) {
            btnDel.disabled = true;
            btnDel.title = 'No se puede eliminar: existen pagos registrados. Anule los pagos en el módulo de Pagos.';
        } else if (estadoConfirmada) {
            btnDel.disabled = true;
            btnDel.title = 'No se puede eliminar: la reserva está confirmada. Cancele la reserva primero si desea terminarla.';
        } else {
            btnDel.disabled = false;
            btnDel.title = 'Eliminar esta reserva del sistema';
        }
    }

    function actualizarMontoPendienteGrupal() {
        if (!datosDetalleActual) return;
        
        const clienteId = parseInt(document.getElementById('registro_cliente_grupal').value);
        if (!clienteId) {
            document.getElementById('registro_monto').value = '';
            return;
        }
        
        const integrante = datosDetalleActual.integrantes.find(i => i.id === clienteId);
        if (integrante) {
            document.getElementById('registro_monto').value = integrante.deuda.toFixed(2);
            document.getElementById('registro_cliente_id').value = clienteId;
        }
    }

    function activarEdicionReserva() {
        document.getElementById('vista_edicion_viaje').classList.remove('d-none');
        document.getElementById('vista_lectura_viaje').classList.add('d-none');
    }

    function cancelarEdicionReserva() {
        if (datosDetalleActual) poblarModalDetalle(datosDetalleActual);
    }

    function guardarEdicionReserva() {
        const id = document.getElementById('edit_reserva_id').value;
        const body = {
            fecha_reserva: document.getElementById('edit_fecha_reserva').value,
            fecha_viaje: document.getElementById('edit_fecha_viaje').value,
            precio_total_viaje: document.getElementById('edit_precio_total').value,
            destino_id: document.getElementById('edit_destino_id').value,
            estado: document.getElementById('edit_estado').value,
        };
        fetch(detalleUrlBase + '/' + id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(body),
        })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => {
                if (!ok) {
                    alert(j.message || (j.errors ? JSON.stringify(j.errors) : 'No se pudo guardar'));
                    return;
                }
                window.location.reload();
            })
            .catch(() => alert('Error de red al guardar.'));
    }

    function confirmarEliminarReserva() {
        if (!datosDetalleActual) return;
        
        // Validación 1: Si tiene pagos no se puede eliminar
        if (datosDetalleActual.pagos_activos > 0) {
            alert(' No se puede eliminar esta reserva.\n\n' +
                  'Razón: Existen ' + datosDetalleActual.pagos_activos + ' pago(s) registrado(s).\n\n' +
                  'Solución: Debe anular los pagos primero en el módulo de Pagos.');
            return;
        }
        
        // Validación 2: Si está confirmada no se puede eliminar
        const estadoActual = (datosDetalleActual.estado || '').toLowerCase();
        if (estadoActual === 'confirmada') {
            alert(' No se puede eliminar esta reserva.\n\n' +
                  'Razón: La reserva está en estado "Confirmada".\n\n' +
                  'Solución: Primero debe cancelar la reserva, luego podrá eliminarla.');
            return;
        }
        
        // Si pasa las validaciones, pedir confirmación
        if (!confirm('¿Desea eliminar esta reserva del sistema?\n\nReserva: ' + datosDetalleActual.codigo_reserva)) return;
        if (!confirm(' Esta acción es DEFINITIVA y no se puede deshacer. ¿Confirma la eliminación?')) return;

        const id = datosDetalleActual.id;
        fetch(detalleUrlBase + '/' + id, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })
            .then(r => r.json().then(j => ({ ok: r.ok, status: r.status, j })))
            .then(({ ok, status, j }) => {
                if (!ok) {
                    const msgError = j.message || 'No se pudo eliminar la reserva';
                    alert(' Error al eliminar:\n\n' + msgError);
                    return;
                }
                alert(' Reserva eliminada correctamente del sistema.');
                window.location.reload();
            })
            .catch(err => {
                console.error('Error:', err);
                alert(' Error de red al intentar eliminar la reserva.');
            });
    }

    function abrirModalCobrarReserva(reservaId, clienteNombre, pendiente) {
        document.getElementById('cobrar_reserva_id').value = reservaId;
        document.getElementById('cobrar_cliente_nombre').value = clienteNombre;
        document.getElementById('cobrar_monto').value = pendiente;
        document.getElementById('cobrar_cliente_id').value = '';
        document.getElementById('cobrar_monto_maximo').value = pendiente; // <aqui cambios: guardar deuda pendiente como maximo de cobro
        document.getElementById('cobrar_monto').max = pendiente; // <aqui cambios: establecer maximo en el input para validación del monto

        var modal = new bootstrap.Modal(document.getElementById('modalCobrarReserva'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const formCobroRapido = document.getElementById('formCobroRapido');
        if (formCobroRapido) {
            formCobroRapido.addEventListener('submit', function (e) {
                const monto = parseFloat(document.getElementById('cobrar_monto').value || '0');
                const maximo = parseFloat(document.getElementById('cobrar_monto_maximo').value || '0');
                if (monto > maximo) {
                    e.preventDefault();
                    alert('El monto no puede ser mayor a la deuda pendiente (€' + maximo.toFixed(2) + ').');
                    document.getElementById('cobrar_monto').focus();
                }
            });
        }
    });

    @if(session('toast_sync') && session('success'))
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('toastSync');
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const t = new bootstrap.Toast(el);
            t.show();
        }
    });
    @endif

    // ... debajo de tu función abrirModalCobrarReserva ...

    function buscarCliente() {
        const documento = document.getElementById('input_buscar_cedula').value.trim();
        const resultado = document.getElementById('resultado_busqueda');
        resultado.innerHTML = '';

        if (!documento) {
            resultado.innerHTML = '<div class="alert alert-warning py-2 mb-0">Ingrese una cédula para buscar el cliente.</div>';
            return;
        }

        fetch(clientesBuscarUrl + '?documento=' + encodeURIComponent(documento), {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                resultado.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + (data.message || 'Cliente no encontrado.') + '</div>';
                return;
            }

            const cliente = data.data;
            const existente = datosDetalleActual.integrantes.some(i => i.id === cliente.id);
            const disabled = existente ? 'disabled' : '';
            const textoExistente = existente ? '<div class="small text-danger">Este cliente ya forma parte del grupo.</div>' : '';

            resultado.innerHTML = `
                <div class="border rounded p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>${cliente.nombres} ${cliente.apellidos}</strong><br>
                            <span class="text-muted">${cliente.email || 'Sin email'}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" ${disabled} onclick="agregarIntegranteEncontrado(${cliente.id})">
                            Añadir al grupo
                        </button>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Monto asignado($)</label>
                        <input id="monto_asignado_nuevo" type="number" step="0.01" min="0" class="form-control form-control-sm" value="0.00">
                    </div>
                    ${textoExistente}
                </div>
            `;

            window.buscarClienteUltimo = cliente;
        })
        .catch(() => {
            resultado.innerHTML = '<div class="alert alert-danger py-2 mb-0">Error al buscar cliente, intente nuevamente.</div>';
        });
    }

    function agregarIntegranteEncontrado(clienteId) {
        const cliente = window.buscarClienteUltimo;
        if (!cliente || cliente.id !== clienteId) {
            alert('Error interno al agregar cliente. Vuelva a buscar.');
            return;
        }

        if (datosDetalleActual.integrantes.some(i => i.id === cliente.id)) {
            alert('Este cliente ya está en la lista actual de integrantes.');
            return;
        }

        const montoInput = document.getElementById('monto_asignado_nuevo');
        const monto = parseFloat(montoInput.value || '0');
        if (Number.isNaN(monto) || monto < 0) {
            alert('Ingrese un monto asignado válido.');
            return;
        }

        nuevosIntegrantes.push({
            cliente_id: cliente.id,
            monto_asignado: monto,
        });

        datosDetalleActual.integrantes.push({
            id: cliente.id,
            nombres: cliente.nombres,
            apellidos: cliente.apellidos,
            email: cliente.email,
            telefono: cliente.telefono,
            monto_asignado: monto,
            pagado: 0,
            deuda: monto,
            es_lider: false,
            es_nuevo: true,
        });

        document.getElementById('resultado_busqueda').innerHTML = '<div class="alert alert-success py-2 mb-0">Cliente agregado temporalmente. Presiona Guardar Cambios.</div>';
        renderListaIntegrantes();
    }

    function abrirGestionIntegrantes() {
        if (!datosDetalleActual) return;
        integrantesEliminados=[];
        nuevosIntegrantes=[];

        let modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modalDetalleReserva'));
        if (modalDetalle) {
            modalDetalle.hide();
        }

        renderListaIntegrantes();
        var modalG = new bootstrap.Modal(document.getElementById('modalGestionIntegrantes'));
        modalG.show();
    }

    function renderListaIntegrantes() {
        const cont = document.getElementById('lista_pasajeros_editar');
        cont.innerHTML = '';

        datosDetalleActual.integrantes.forEach(i => {
            const esNuevo = !!i.es_nuevo;
            const badge = esNuevo ? '<span class="badge bg-success me-2">Nuevo</span>' : '';
            const deuda = i.deuda !== undefined ? Number(i.deuda).toFixed(2) : '0.00';
            const asignado = i.monto_asignado !== undefined ? Number(i.monto_asignado).toFixed(2) : '0.00';
            const esLider = i.es_lider;
            const botonLider = esLider
                ? `<span class="badge bg-primary">● Líder</span>`
                : `<button class="btn btn-sm btn-outline-primary ms-2" onclick="cambiarLider(${i.id})">Hacer líder</button>`;
            const div = document.createElement('div');
            div.className = "d-flex justify-content-between align-items-center border rounded p-2 mb-2";
            div.innerHTML = `
                <div>
                    ${badge}<strong>${i.nombres} ${i.apellidos}</strong><br>
                    <small class="text-muted">${i.email || 'Sin email'}</small><br>
                    <small class="text-muted">Asignado: €${asignado} · Deuda: €${deuda}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${botonLider}
                    <button class="btn btn-sm btn-danger" onclick="quitarIntegrante(${i.id})">
                        Quitar
                    </button>
                </div>
            `;
            cont.appendChild(div);
        });

        if (datosDetalleActual.integrantes.length === 0) {
            cont.innerHTML = '<div class="alert alert-secondary py-2 mb-0">No hay integrantes registrados para esta reserva.</div>';
        }
    }

    function quitarIntegrante(clienteId) {
        const index = datosDetalleActual.integrantes.findIndex(i => i.id === clienteId);
        if (index === -1) return;

        const integrante = datosDetalleActual.integrantes[index];

        if (integrante.es_nuevo) {
            nuevosIntegrantes = nuevosIntegrantes.filter(i => i.cliente_id !== clienteId);
        } else {
            if (!integrantesEliminados.includes(clienteId)) {
                integrantesEliminados.push(clienteId);
            }
        }

        datosDetalleActual.integrantes.splice(index, 1);
        renderListaIntegrantes();
    }

    function cambiarLider(clienteId){
        //console.log("Se hizo clic en el boton para el ID:", clienteId);
        if(!datosDetalleActual)return
        const integrante = datosDetalleActual.integrantes.find(i=>i.id===clienteId);
        if(!integrante||integrante.es_lider)return;
        //quitar lider al actual y asignr uno nuevo
        datosDetalleActual.integrantes.forEach(i=>{
            i.es_lider = (i.id === clienteId);
        });
        nuevoLiderId=clienteId;
        renderListaIntegrantes();
    }

    function guardarCambiosIntegrantes() {
        if (!datosDetalleActual) {
            alert('No se encontró la reserva para gestionar integrantes.');
            return;
        }

        if (nuevosIntegrantes.length === 0 && integrantesEliminados.length === 0 && nuevoLiderId === null) {
            alert('No hay cambios pendientes en los integrantes.');
            return;
        }

        const payload = {
            reserva_id: datosDetalleActual.id,
            nuevos_integrantes: nuevosIntegrantes,
            integrantes_eliminados: integrantesEliminados,
            nuevo_lider_id:nuevoLiderId
        };

        fetch(detalleUrlBase + '/' + datosDetalleActual.id + '/integrantes/guardar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                alert(data.message || 'No se pudo guardar la lista de integrantes.');
                return;
            }

            alert('Cambios guardados correctamente.');
            nuevosIntegrantes = [];
            integrantesEliminados = [];
            nuevoLiderId=null;

            fetch(detalleUrlBase + '/' + datosDetalleActual.id + '/detalle', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(json => {
                    if (json.success) {
                        datosDetalleActual = json.data;
                        poblarModalDetalle(json.data);
                    }
                })
                .catch(() => {
                    window.location.reload();
                });

            const modalG = bootstrap.Modal.getInstance(document.getElementById('modalGestionIntegrantes'));
            if (modalG) modalG.hide();
        })
        .catch(() => {
            alert('Error de red al guardar los cambios de integrantes.');
        });
    }
    
    function activarEdicionRapida(td) {
        const span = td.querySelector('.text-content');
        const input = td.querySelector('.input-edit');
        const icon = td.querySelector('.edit-icon');

        if (input.classList.contains('d-none')) {
            span.classList.add('d-none');
            icon.classList.add('d-none');
            input.classList.remove('d-none');
            input.focus();
        }
    }

    function finalizarEdicionRapida(input, campo, integranteId) {
        const td = input.closest('td');
        const span = td.querySelector('.text-content');
        const icon = td.querySelector('.edit-icon');
        const nuevoValor = input.value;
        let valorAnterior = span.textContent.trim();

        // Para campos monetarios, remover el símbolo € antes de comparar
        if (campo === 'monto_asignado') {
            valorAnterior = valorAnterior.replace('€', '').trim();
        }

        // si el valos no cambio , solo cerramos el modo edición
        if(nuevoValor==valorAnterior){
            input.classList.add('d-none');
            span.classList.remove('d-none');
            if(icon) icon.classList.remove('d-none');
            return;
        }
        // Si el valor es vacío lo revertimos
        if(nuevoValor==""&& campo!== 'telefono'){
            alert('el campo no puede estar vacío');
            input.value = valorAnterior;
            input.classList.add('d-none');
            span.classList.remove('d-none');
            return;
        }
            
        // 3. Petición AJAX al servidor
        fetch(`/reservas/integrantes/${integranteId}/update-fast`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                campo: campo,
                valor: nuevoValor,
                reserva_id: datosDetalleActual.id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualización visual exitosa
                if (campo === 'monto_asignado') {
                    span.textContent = '€' + Number(nuevoValor).toFixed(2);

                    // Refrescar datos del detalle en el modal SIN cerrar/reabrir el modal.
                    if (datosDetalleActual && datosDetalleActual.id) {
                        fetch(detalleUrlBase + '/' + datosDetalleActual.id + '/detalle', { headers: { 'Accept': 'application/json' } })
                            .then(r => r.json())
                            .then(json => {
                                if (json.success) {
                                    datosDetalleActual = json.data;
                                    poblarModalDetalle(json.data);
                                }
                            })
                            .catch(() => {
                                // ignore, ya tenemos al menos el monto visualmente actualizado.
                            });
                    }
                } else {
                    span.textContent = nuevoValor;
                }

                // Opcional: Una pequeña animación de éxito (destello verde)
                td.style.backgroundColor = '#d4edda';
                setTimeout(() => td.style.backgroundColor = '', 500);
            } else {
                // Error validado por Laravel (ej: email duplicado)
                alert("Error: " + data.message);
                input.value = valorAnterior;
            }
        })
        .catch(error => {
            console.error("Error en la petición:", error);
            alert("No se pudo conectar con el servidor para guardar el cambio.");
            input.value = valorAnterior;
        })
        .finally(() => {
            // Siempre volvemos al estado visual normal
            input.classList.add('d-none');
            span.classList.remove('d-none');
            if(icon) icon.classList.remove('d-none');
        });
    }
</script>

@endsection
