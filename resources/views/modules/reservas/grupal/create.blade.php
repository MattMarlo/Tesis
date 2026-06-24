@extends('layouts.main')

@section('content')
<style>
    body {
        background-color: #F8FAFC;
        color: #f1f3f5;
    }
    .text-secondary {
        color: #150f0b !important;
    }
    .form-control, .form-select {
        background-color: #fcfcff !important;
        border-color: #2c303e !important;
        color: #080101 !important;
    }
    .form-control:focus, .form-select:focus {
        background-color: #aeaebb !important;
        border-color: #5b66d6 !important;
        box-shadow: 0 0 0 .25rem rgba(91, 102, 214, .25);
    }
    .card {
        background-color: #909fc2;
        border: 1px solid #0c36ce;
    }
    .btn-primary {
        background-color: #5b66d6;
        border-color: #5b66d6;
    }
    .btn-primary:hover {
        background-color: #4a54bf;
        border-color: #4a54bf;
    }
    .btn-outline-secondary {
        border-color: #434a5c;
        color: #1034aa;
    }
    .btn-outline-secondary:hover {
        background-color: #b9320c;
        color: #fff;
    }
    .member-row {
        background-color: #9d9dac;
        border-bottom: 1px solid #0c0354;
        padding: 12px 0;
    }
    .badge-leader {
        background-color: rgba(251, 251, 255, 0.957);
        color: #5d0606;
        border: 1px solid rgba(91, 102, 214, 0.4);
    }
    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
    }
    table {
        color: #c9ccd6;
    }
    .bg-status-success {
        background-color: #eff1f7;
        border: 1px solid #198754;
    }
    .badge-estado-pago {
        background-color: #fdf3f3;
        border: 1px solid #d10b0b;
        color: #7e120a;
    }
    .progress {
        background-color: #b8c3eb;
    }
    .list-group-item-suggestion {
        background-color: #271a21;
        color: #fff;
        border: 1px solid #2c303e;
        border-bottom: none;
        cursor: pointer;
    }
    .list-group-item-suggestion:last-child {
        border-bottom: 1px solid #3e362c;
    }
    .list-group-item-suggestion:hover {
        background-color: #3e2c2c;
    }
</style>

<div class="container-fluid py-4 min-vh-100" style="background-color: #F8FAFC;">
    <form action="{{ route('reservas_grupal.store') }}" method="POST" id="reservaForm">
        @csrf
        <div id="errores-generales" class="alert alert-danger d-none"></div>
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="fw-bold text-black mb-1">Nueva reserva grupal</h3>
                    <p class="text-secondary mb-0">Configura el grupo, integrantes y distribución de pagos</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reservas') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Crear reserva grupal</button>
                </div>
            </div>
        </div>

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <!-- Card 1: Datos del viaje grupal -->
                <div class="card rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4 text-black">Datos del viaje grupal</h6>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">NOMBRE DEL GRUPO</label>
                                <div class="position-relative">
                                    <input type="hidden" name="grupo_id" id="grupo_id" value="{{ old('grupo_id') }}">
                                    <input type="text" name="nombre_grupo" id="nombre_grupo" class="form-control rounded-3" placeholder="Ej: Familia Gómez, Tour Europa..." autocomplete="off" value="{{ old('nombre_grupo') }}">
                                    <ul id="grupos-sugerencias" class="list-group position-absolute w-100 d-none mt-1 shadow-lg" style="z-index: 1000; border-radius: 0.5rem; overflow: hidden;">
                                    </ul>
                                </div>
                                <small class="text-secondary mt-1 d-block" id="grupo-status">Escribe para buscar o crear un nuevo grupo.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold">DESTINO</label>
                                <select name="destino_id" class="form-select rounded-3" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($destinos as $destino)
                                        <option value="{{ $destino->id }}" {{ old('destino_id') == $destino->id ? 'selected' : '' }}>{{ $destino->pais }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold">FECHA DE VIAJE</label>
                                <input type="date" name="fecha_viaje" class="form-control rounded-3" value="{{ old('fecha_viaje') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">MONTO TOTAL GRUPO (€)</label>
                                <input type="number" step="0.01" name="precio_total_viaje" id="monto_total_grupo" class="form-control rounded-3" value="{{ old('precio_total_viaje', 0) }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">ESTADO</label>
                                <select name="estado" class="form-select rounded-3">
                                    <option value="pendiente" {{ old('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="confirmada" {{ old('estado') == 'confirmada' ? 'selected' : '' }}>Confirmado</option>
                                </select>
                                <!-- hidden date field for form validation -->
                                <input type="hidden" name="fecha_reserva" value="{{ old('fecha_reserva', date('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Integrantes del grupo -->
                <div class="card rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="fw-bold mb-1 text-black">Integrantes del grupo</h6>
                                <p class="text-secondary small mb-0"><span id="count-integrantes">0</span> integrantes agregados</p>
                            </div>
                            <div class="d-flex gap-2">
                                <select id="buscador_clientes" class="form-select form-select-sm rounded-pill px-3" style="width: 200px;">
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach($clientes as $cliente)
                                        @php
                                            $nomb = explode(' ', $cliente->nombres)[0] . ' ' . explode(' ', $cliente->apellidos)[0];
                                            $iniciales = substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1);
                                        @endphp
                                        <option value="{{ $cliente->id }}" data-iniciales="{{ strtoupper($iniciales) }}" data-email="{{ strtolower($nomb) }}@email.com">{{ $nomb }}</option>
                                    @endforeach
                                </select>
                                <button type="button" onclick="agregarIntegrante()" class="btn btn-sm btn-outline-secondary rounded-pill px-3">+ Agregar</button>
                            </div>
                        </div>
                        
                        <div id="integrantes-lista" class="d-flex flex-column"></div>
                    </div>
                </div>

                <!-- Card 3: Distribución de pagos -->
                <div class="card rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="fw-bold mb-1 text-black">Distribución de pagos</h6>
                                <p class="text-secondary small mb-0">Total: €<span id="dist-total">0</span> · <span id="dist-count">0</span> integrantes</p>
                            </div>
                        </div>
                        
                        <!-- Progress -->
                        <div class="progress mb-4 rounded-pill" style="height: 6px;" id="progress-bar-container"></div>
                        
                        <!-- Header Table -->
                        <div class="row text-secondary small fw-bold mb-2 pb-2 px-3 align-items-center border-bottom" style="border-color: #2c303e !important;">
                            <div class="col-4">INTEGRANTE</div>
                            <div class="col-3 text-center">MONTO ASIGNADO (€)</div>
                            <div class="col-2 text-center">% DEL TOTAL</div>
                            <div class="col-3 text-end">ESTADO PAGO</div>
                        </div>
                        
                        <div id="distribucion-lista" class="d-flex flex-column"></div>
                        
                        <!-- Status Bar -->
                        <div id="dist-status" class="mt-4 p-3 rounded-3 d-none bg-status-success">
                            <p class="text-success mb-0 fw-bold small"><i class="bi bi-check-circle-fill me-2"></i> Distribución correcta — todos los integrantes tienen monto asignado.</p>
                        </div>
                        <div id="dist-error" class="mt-4 p-3 rounded-3 d-none" style="background-color: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.4);">
                            <p class="text-danger mb-0 fw-bold small"><i class="bi bi-exclamation-triangle-fill me-2"></i> La distribución no coincide con el total. (<span id="dist-dif"></span>)</p>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Derecha: Resumen del Grupo (Opcional, se puede dejar vacío o añadir un sidebar) -->
            <div class="col-lg-4 d-none d-lg-block">
                <!-- Se podría poner un mini resumen si se desea, según el area oculta en tu screenshot -->
            </div>
        </div>
    </form>
</div>

<script>
let integrantes = [];
let idxCounter = 0;
const colores = ['#5b66d6', '#198754', '#fd7e14', '#dc3545', '#0dcaf0', '#6f42c1'];

const gruposExistentes = @json($grupos);
const clientesMap = {};
@foreach($clientes as $c)
    clientesMap['{{ $c->id }}'] = {
        id: '{{ $c->id }}',
        nombre: '{{ addslashes(trim(explode(' ', $c->nombres)[0] . " " . explode(' ', $c->apellidos)[0])) }}',
        iniciales: '{{ strtoupper(substr($c->nombres,0,1) . substr($c->apellidos,0,1)) }}',
        email: '{{ strtolower(trim(explode(' ', $c->nombres)[0])) }}@email.com'
    };
@endforeach
const inputNombreGrupo = document.getElementById('nombre_grupo');
const inputGrupoId = document.getElementById('grupo_id');
const sugerenciasBox = document.getElementById('grupos-sugerencias');

inputNombreGrupo.addEventListener('input', function() {
    const term = this.value.toLowerCase().trim();
    inputGrupoId.value = ''; // Reset ID if user types
    sugerenciasBox.innerHTML = '';
    
    if (term === '') {
        sugerenciasBox.classList.add('d-none');
        document.getElementById('grupo-status').innerHTML = 'Escribe para buscar o crear un nuevo grupo.';
        return;
    }

    const matches = gruposExistentes.filter(g => g.nombre_grupo.toLowerCase().includes(term));
    
    if (matches.length > 0) {
        matches.forEach(g => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-suggestion px-3 py-2';
            li.textContent = g.nombre_grupo;
            li.onclick = function() {
                inputNombreGrupo.value = g.nombre_grupo;
                inputGrupoId.value = g.id;
                sugerenciasBox.classList.add('d-none');
                document.getElementById('grupo-status').innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Grupo existente seleccionado</span>';
            };
            sugerenciasBox.appendChild(li);
        });
        sugerenciasBox.classList.remove('d-none');
        document.getElementById('grupo-status').innerText = 'Escribe para buscar o crear un nuevo grupo.';
    } else {
        sugerenciasBox.classList.add('d-none');
        document.getElementById('grupo-status').innerHTML = '<span class="text-info fw-bold"><i class="bi bi-info-circle-fill"></i> Se creará un nuevo grupo</span>';
    }
});

// Reconstruir la lista de integrantes si hay datos previos (por ejemplo, after withInput)
const oldIntegrantes = @json(old('integrantes', []));
if (oldIntegrantes && oldIntegrantes.length > 0) {
    oldIntegrantes.forEach((oi) => {
        const clienteId = oi['cliente_id'];
        const cliente = clientesMap[clienteId];
        if (!cliente) return;
        const isLider = oi['es_lider'] && (oi['es_lider'] == 1 || oi['es_lider'] === true || oi['es_lider'] === '1');
        const monto = parseFloat(oi['monto_asignado']) || 0;
        const color = colores[integrantes.length % colores.length];
        integrantes.push({ idx: idxCounter, id: clienteId, nombre: cliente.nombre, iniciales: cliente.iniciales, email: cliente.email, color, monto: monto, isLider });
        idxCounter++;
    });
    renderIntegrantes();
    calcularDistribucion();
}

document.addEventListener('click', function(e) {
    if (e.target !== inputNombreGrupo && e.target !== sugerenciasBox) {
        sugerenciasBox.classList.add('d-none');
    }
});

function agregarIntegrante() {
    const sel = document.getElementById('buscador_clientes');
    const id = sel.value;
    const nombre = sel.options[sel.selectedIndex].text;
    const iniciales = sel.options[sel.selectedIndex].getAttribute('data-iniciales');
    const email = sel.options[sel.selectedIndex].getAttribute('data-email');
    
    if(!id || integrantes.find(i => i.id == id)) return;

    const isLider = integrantes.length === 0; // Primer agregado es lider
    const color = colores[integrantes.length % colores.length];

    integrantes.push({ idx: idxCounter, id, nombre, iniciales, email, color, monto: 0, isLider });
    idxCounter++;
    
    sel.value = ""; // reset
    renderIntegrantes();
    calcularDistribucion();
}

function setLider(id) {
    integrantes.forEach(i => i.isLider = (i.id == id));
    renderIntegrantes();
}

function remover(id) {
    integrantes = integrantes.filter(i => i.id != id);
    if(integrantes.length > 0 && !integrantes.find(i => i.isLider)) {
        integrantes[0].isLider = true;
    }
    renderIntegrantes();
    calcularDistribucion();
}

function renderIntegrantes() {
    const ls = document.getElementById('integrantes-lista');
    ls.innerHTML = '';
    
    document.getElementById('count-integrantes').innerText = integrantes.length;
    document.getElementById('dist-count').innerText = integrantes.length;
    
    integrantes.forEach(i => {
        // Render in List 1
        const lblLider = i.isLider 
          ? `<span class="badge badge-leader rounded-pill px-3 py-2 ms-auto">● Líder</span><input type="hidden" name="integrantes[${i.idx}][es_lider]" value="1">` 
          : `<button type="button" class="btn btn-sm btn-link text-secondary ms-auto text-decoration-none" onclick="setLider('${i.id}')">Hacer líder</button><input type="hidden" name="integrantes[${i.idx}][es_lider]" value="0">`;
        
        ls.innerHTML += `
            <div class="d-flex align-items-center w-100 member-row py-3">
                <input type="hidden" name="integrantes[${i.idx}][cliente_id]" value="${i.id}">
                <div class="avatar-circle me-3 text-white" style="background-color: ${i.color}">${i.iniciales}</div>
                <div>
                    <h6 class="mb-0 text-white fw-semibold">${i.nombre}</h6>
                    <small class="text-secondary">${i.email}</small>
                </div>
                ${lblLider}
                ${!i.isLider ? `<button type="button" class="btn text-secondary border-0 ms-2 px-2" onclick="remover('${i.id}')"><i class="bi bi-trash"></i></button>` : ''}
            </div>
        `;
    });
}

function setModoDistribucion(modo) {
    // Modo personalizado es el único disponible ahora
}

function calcularDistribucion() {
    // Calcular el total basado en los montos asignados a cada integrante
    let sumAsignado = 0;
    integrantes.forEach((i) => {
        sumAsignado += i.monto;
    });
    
    const total = parseFloat(sumAsignado.toFixed(2));
    
    // Actualizar el campo de monto total grupo
    document.getElementById('monto_total_grupo').value = total.toFixed(2);
    document.getElementById('dist-total').innerText = total.toFixed(2);
    
    if(integrantes.length === 0) {
        document.getElementById('distribucion-lista').innerHTML = '';
        document.getElementById('progress-bar-container').innerHTML = '';
        document.getElementById('dist-status').classList.add('d-none');
        document.getElementById('dist-error').classList.add('d-none');
        return;
    }

    renderDistribucion(total);
}

function manualInputMonto(idxList, elem) {
    integrantes[idxList].monto = parseFloat(elem.value) || 0;
    calcularDistribucion();
    const total = parseFloat(document.getElementById('monto_total_grupo').value) || 0;
    renderDistribucion(total, true);
}

function renderDistribucion(total, onlyUpdateProgressAndTotals = false) {
    const ls = document.getElementById('distribucion-lista');
    const pb = document.getElementById('progress-bar-container');
    
    if(!onlyUpdateProgressAndTotals) {
        ls.innerHTML = '';
        pb.innerHTML = '';
    }

    let sumAsignado = 0;
    
    integrantes.forEach((i, ind) => {
        sumAsignado += i.monto;
        let pct = total > 0 ? (i.monto / total) * 100 : 0;
        
        if(!onlyUpdateProgressAndTotals) {
            ls.innerHTML += `
                <div class="row w-100 align-items-center member-row py-3 px-3 m-0">
                    <div class="col-4 d-flex align-items-center">
                        <div class="avatar-circle me-2 text-white" style="background-color: ${i.color}; width: 24px; height: 24px; font-size: 0.6rem;">${i.iniciales}</div>
                        <span class="text-white fw-semibold small">${i.nombre}
                        ${i.isLider ? '<span class="badge badge-leader ms-2" style="font-size:0.6rem; padding: 3px 6px;">● Líder</span>' : ''}
                        </span>
                    </div>
                    <div class="col-3 px-4">
                        <input type="number" step="0.00" name="integrantes[${i.idx}][monto_asignado]" class="form-control rounded-3 text-end" value="${i.monto.toFixed(2)}" oninput="manualInputMonto(${ind}, this)">
                    </div>
                    <div class="col-2 text-center text-secondary small" id="pct-${i.id}">
                        ${pct.toFixed(0)}%
                    </div>
                    <div class="col-3 text-end">
                        <span class="badge badge-estado-pago rounded-pill px-3 py-2 fw-normal">● Pendiente</span>
                    </div>
                </div>
            `;
            pb.innerHTML += `
                <div class="progress-bar" role="progressbar" id="pb-${i.id}" style="width: ${pct}%; background-color: ${i.color}"></div>
            `;
        } else {
            document.getElementById(`pct-${i.id}`).innerText = pct.toFixed(0) + '%';
            document.getElementById(`pb-${i.id}`).style.width = pct + '%';
        }
    });

    const diff = total - sumAsignado;
    if(Math.abs(diff) <= 0.05) {
        document.getElementById('dist-status').classList.remove('d-none');
        document.getElementById('dist-error').classList.add('d-none');
    } else {
        document.getElementById('dist-status').classList.add('d-none');
        document.getElementById('dist-error').classList.remove('d-none');
        document.getElementById('dist-dif').innerText = (diff > 0 ? 'Faltan €' : 'Sobran €') + Math.abs(diff).toFixed(2);
    }
}


// <- cambio 3: capturo el formulario y evito su envío tradicional
document.getElementById('reservaForm').addEventListener('submit', async function(e) {
    // <- cambio 4: evita que la página se recargue
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // <- cambio 5: deshabilito el botón y muestro "Guardando..." mientras se envía
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Guardando...';

    try {
        // <- cambio 6: envío los datos con fetch (AJAX)
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'   // <- esperamos respuesta JSON
            },
            body: formData
        });

        const data = await response.json();

        // <- cambio 7: si la respuesta es exitosa, redirijo a la lista de reservas
        if (response.ok && data.success) {
            window.location.href = data.redirect;
        }
        // <- cambio 8: si hay errores de validación (código 422)
        else if (response.status === 422 && data.errors) {
            // Limpio errores anteriores
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            // Recorro cada error
            for (const [key, messages] of Object.entries(data.errors)) {
                let input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    // Si el error pertenece a un campo específico, lo marco en rojo
                    input.classList.add('is-invalid');
                    let feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = messages[0];
                    input.parentNode.appendChild(feedback);
                } else {
                    // Si el error no tiene campo asociado, lo muestro en el div general
                    let errorDiv = document.getElementById('errores-generales');
                    errorDiv.innerHTML = messages[0];
                    errorDiv.classList.remove('d-none');
                }
            }
        }
        // <- cambio 9: otros errores (500, etc.)
        else {
            let errorDiv = document.getElementById('errores-generales');
            errorDiv.innerHTML = data.message || 'Error inesperado. Intente de nuevo.';
            errorDiv.classList.remove('d-none');
        }
    } catch (error) {
        // <- cambio 10: error de red o conexión
        console.error(error);
        let errorDiv = document.getElementById('errores-generales');
        errorDiv.innerHTML = 'Error de conexión. Revisa tu internet.';
        errorDiv.classList.remove('d-none');
    } finally {
        // <- cambio 11: vuelvo a habilitar el botón con el texto original
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>
@endsection