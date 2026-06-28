<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Reserva;
use App\Models\Cliente;
use App\Services\PagoService;
use App\Services\ReservaService;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservaController extends Controller
{
    public function __construct(protected PagoService $pagoService , protected ReservaService $reservaService)
    {
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /*
        $reservas=DB::table('reservas as r')
            ->select(
                'r.id',
                'r.codigo_reserva',
                'r.tipo',
                'c.nombres',
                'c.apellidos',
                'd.pais',
                'r.fecha_viaje',
                'r.precio_total_viaje',
                'r.estado',
                'r.estado_pago'
            )
            ->join('clientes as c','r.cliente_id','=','c.id')
            ->join('destinos as d','r.destino_id','=','d.id')
            ->orderBy('r.id', 'desc')
            ->paginate(10);*/
        
        $titulo='Reservas';
        $reservas = DB::table('reservas as r')
        ->select(
            'r.id',
            'r.codigo_reserva',
            'r.tipo',
            'c.nombres',
            'c.apellidos',
            'd.pais',
            'r.fecha_viaje',
            'r.precio_total_viaje',
            'r.estado',
            'r.estado_pago',
            DB::raw('MAX(g.nombre_grupo) as nombre_grupo'),
            DB::raw('COALESCE(SUM(p.monto_depositado),0) as total_depositado')
        )
        ->join('clientes as c','r.cliente_id','=','c.id')
        ->join('destinos as d','r.destino_id','=','d.id')
        ->leftJoin('pagos as p','r.id','=','p.reserva_id') 
        ->leftJoin('reservas_grupos as rg', 'r.id', '=', 'rg.reserva_id') 
        ->leftJoin('grupos as g', 'rg.grupo_id', '=', 'g.id') 
        ->groupBy(
            'r.id',
            'r.codigo_reserva',
            'r.tipo',
            'c.nombres',
            'c.apellidos',
            'd.pais',
            'r.fecha_viaje',
            'r.precio_total_viaje',
            'r.estado',
            'r.estado_pago',
            'g.nombre_grupo'
        )
        ->orderBy('r.id', 'desc')
        ->paginate(10);
       
        return view('modules.reservas.index', compact('reservas','titulo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * 
     * LÓGICA DE NEGOCIO:
     *  PERMITIR eliminar SI:
     *    - Estado es "pendiente" Y NO hay pagos
     *    - Estado es "cancelada" Y NO hay pagos
     * 
     *  NO permitir eliminar SI:
     *    - Hay pagos registrados (anularlos primero)
     *    - Estado es "confirmada" (cancelar primero)
     */
    public function destroy(Request $request, string $id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificación 1: Hay pagos registrados?
        $totalPagos = DB::table('pagos')->where('reserva_id', $reserva->id)->sum('monto_depositado');
        if ($totalPagos > 0) {
            $msg = 'No se puede eliminar: existen pagos por €'.number_format($totalPagos, 2).' registrados. Debe anularlos primero en el módulo de Pagos.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('reservas')->with('error', $msg);
        }

        // Verificación 2: Estado debe ser "pendiente" o "cancelada"
        $estadoActual = strtolower($reserva->estado);
        $estadosPermitidos = ['pendiente', 'cancelada'];
        
        if (!in_array($estadoActual, $estadosPermitidos)) {
            $estadoFormato = ucfirst($estadoActual);
            $msg = 'No se puede eliminar: la reserva está en estado "'.$estadoFormato.'". ' .
                   'Solo se pueden eliminar reservas en estado Pendiente o Cancelada. ' .
                   'Si es necesario eliminarla, primero cancélela.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('reservas')->with('error', $msg);
        }

        // Pasó todas las validaciones: proceder con eliminación
        try {
            DB::transaction(function () use ($reserva) {
                // Eliminar relación de grupo si es grupal
                if ($reserva->tipo === 'grupal') {
                    DB::table('reservas_grupos')->where('reserva_id', $reserva->id)->delete();
                }
                // Eliminar la reserva
                $reserva->delete();
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Reserva ' . $reserva->codigo_reserva . ' eliminada correctamente del sistema.'
                ]);
            }
            return redirect()->route('reservas')->with('success', 'Reserva eliminada correctamente.');
        } catch (\Exception $e) {
            $msgError = 'Error al eliminar la reserva: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msgError], 500);
            }
            return redirect()->route('reservas')->with('error', $msgError);
        }
    }

    public function detalleJson(string $id)
    {
        $reserva = Reserva::with(['cliente', 'destino', 'user', 'reservaGrupo.grupo', 'pago'])->findOrFail($id);

        $totalPagado = (float) $reserva->pago->sum('monto_depositado');
        $pagosActivos = $reserva->pago->count();

        $titularNombre = $reserva->cliente
            ? trim($reserva->cliente->nombres.' '.$reserva->cliente->apellidos)
            : '';

        $grupoNombre = null;
        $integrantes = [];
        
        if ($reserva->tipo === 'grupal' && $reserva->reservaGrupo && $reserva->reservaGrupo->grupo) {
            $grupoNombre = $reserva->reservaGrupo->grupo->nombre_grupo;
            
            // Obtener todos los integrantes del grupo
            $grupo_id = $reserva->reservaGrupo->grupo_id;
            $integrantesDB = DB::table('grupos_clientes')
                ->join('clientes', 'grupos_clientes.cliente_id', '=', 'clientes.id')
                ->where('grupos_clientes.grupo_id', $grupo_id)
                ->select(
                    'clientes.id',
                    'clientes.nombres',
                    'clientes.apellidos',
                    'clientes.email',
                    'clientes.telefono',
                    'clientes.archivo',
                    'grupos_clientes.monto_asignado',
                    'grupos_clientes.es_lider'
                )
                ->get();
            
            // Calcular deuda de cada integrante
            foreach ($integrantesDB as $integrante) {
                $pagosIntegrante = $reserva->pago->where('cliente_id', $integrante->id)->sum('monto_depositado');
                $deuda = max(0, $integrante->monto_asignado - $pagosIntegrante);
                
                $integrantes[] = [
                    'id' => $integrante->id,
                    'nombres' => $integrante->nombres,
                    'apellidos' => $integrante->apellidos,
                    'email' => $integrante->email,
                    'telefono' => $integrante->telefono,
                    'archivo'=>$integrante->archivo,
                    'monto_asignado' => (float) $integrante->monto_asignado,
                    'pagado' => (float) $pagosIntegrante,
                    'deuda' => (float) $deuda,
                    'es_lider' => (bool) $integrante->es_lider,
                ];
            }
        }

        $destinos = Destino::query()->orderBy('pais')->get(['id', 'pais']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                 => $reserva->id,
                'codigo_reserva'     => $reserva->codigo_reserva,
                'tipo'               => $reserva->tipo,
                'fecha_creacion'     => $reserva->created_at?->format('d/m/Y H:i'),
                'fecha_reserva'      => $reserva->fecha_reserva,
                'fecha_viaje'        => $reserva->fecha_viaje,
                'precio_total_viaje' => (float) $reserva->precio_total_viaje,
                'estado'             => $reserva->estado,
                'estado_pago'        => $reserva->estado_pago,
                'total_depositado'   => $totalPagado,
                'pagos_activos'      => $pagosActivos,
                'cliente_id'         => $reserva->cliente_id,
                'titular'            => [
                    'nombres'   => $reserva->cliente->nombres ?? '',
                    'apellidos' => $reserva->cliente->apellidos ?? '',
                    'email'     => $reserva->cliente->email ?? '',
                    'telefono'  => $reserva->cliente->telefono ?? '',
                    'nombre_completo' => $titularNombre,
                    'archivo'   => $reserva->cliente->archivo ?? null,
                ],
                'destino'            => [
                    'id'   => $reserva->destino->id ?? null,
                    'pais' => $reserva->destino->pais ?? '',
                ],
                'grupo_nombre'       => $grupoNombre,
                'integrantes'        => $integrantes,
                'itinerario_resumen' => ($reserva->destino ? 'Destino: '.$reserva->destino->pais.'. Salida: '.\Carbon\Carbon::parse($reserva->fecha_viaje)->format('d/m/Y') : ''),
                'destinos_opciones'  => $destinos,
            ],
            
        ]);
    }

    public function update(Request $request, string $id)
    {
        $reserva = Reserva::findOrFail($id);

        $request->validate([
            'fecha_viaje'        => 'required|date',
            'fecha_reserva'      => 'required|date',
            'precio_total_viaje' => 'required|numeric|min:0',
            'destino_id'         => 'required|exists:destinos,id',
            'estado'             => 'required|in:confirmada,pendiente,cancelada',
        ]);

        // Validación: la fecha de viaje no debe ser anterior a la fecha de reserva
        if ($request->filled('fecha_viaje') && $request->filled('fecha_reserva')) {
            $fechaViaje = Carbon::parse($request->input('fecha_viaje'));
            $fechaReserva = Carbon::parse($request->input('fecha_reserva'));
            if ($fechaViaje->lt($fechaReserva)) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'La fecha de viaje no debe ser antes de la fecha de reserva!!'], 422);
                }
                return back()->with('error', 'La fecha de viaje no debe ser antes de la fecha de reserva')->withInput();
            }
        }
        $reserva->fill($request->only([
            'fecha_viaje',
            'fecha_reserva',
            'precio_total_viaje',
            'destino_id',
            'estado',
        ]));
        $reserva->save();

        $this->pagoService->sincronizarEstadoPagoReserva($reserva->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Reserva actualizada.']);
        }

        return redirect()->route('reservas')->with('success', 'Reserva actualizada correctamente.');
    }

    public function guardarIntegrantes(Request $request, string $reservaId)
    {
        $request->validate([
            'reserva_id'            => 'required|integer|exists:reservas,id',
            'nuevos_integrantes'    => 'nullable|array',
            'nuevos_integrantes.*.cliente_id' => 'required_with:nuevos_integrantes|integer|exists:clientes,id',
            'nuevos_integrantes.*.monto_asignado' => 'required_with:nuevos_integrantes|numeric|min:0',
            'integrantes_eliminados' => 'nullable|array',
            'integrantes_eliminados.*' => 'integer|exists:clientes,id',
        ]);

        $reserva = Reserva::with('reservaGrupo.grupo')->findOrFail($reservaId);
        if ($reserva->tipo !== 'grupal' || !$reserva->reservaGrupo || !$reserva->reservaGrupo->grupo) {
            return response()->json([
                'success' => false,
                'message' => 'La reserva no corresponde a un grupo válido.'
            ], 422);
        }

        $grupoId = $reserva->reservaGrupo->grupo_id;
        $actuales = DB::table('grupos_clientes')
            ->where('grupo_id', $grupoId)
            ->pluck('cliente_id')
            ->toArray();

        $nuevos = $request->input('nuevos_integrantes', []);
        $eliminados = $request->input('integrantes_eliminados', []);

        DB::beginTransaction();
        try {
            if (!empty($eliminados)) {
                foreach ($eliminados as $clienteId) {
                    if (!in_array($clienteId, $actuales, true)) {
                        continue;
                    }

                    $grupoCliente = DB::table('grupos_clientes')
                        ->where('grupo_id', $grupoId)
                        ->where('cliente_id', $clienteId)
                        ->first();

                    if ($grupoCliente && $grupoCliente->es_lider) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'No se puede quitar al líder del grupo. Debe cambiar el líder antes de eliminarlo.'
                        ], 422);
                    }

                    $tienePagos = DB::table('pagos')
                        ->where('reserva_id', $reserva->id)
                        ->where('cliente_id', $clienteId)
                        ->exists();

                    if ($tienePagos) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'No se puede quitar el integrante porque tiene pagos registrados.'
                        ], 422);
                    }
                }

                DB::table('grupos_clientes')
                    ->where('grupo_id', $grupoId)
                    ->whereIn('cliente_id', $eliminados)
                    ->delete();
            }

            if (!empty($nuevos)) {
                foreach ($nuevos as $integrante) {
                    $clienteId = (int) $integrante['cliente_id'];
                    $montoAsignado = (float) $integrante['monto_asignado'];

                    if (in_array($clienteId, $actuales, true)) {
                        continue;
                    }

                    $clienteExiste = Cliente::where('id', $clienteId)->exists();
                    if (!$clienteExiste) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'El cliente solicitado no existe.'
                        ], 422);
                    }

                    DB::table('grupos_clientes')->insert([
                        'grupo_id' => $grupoId,
                        'cliente_id' => $clienteId,
                        'monto_asignado' => $montoAsignado,
                        'es_lider' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            //Cambio de lider 
            if($request->has('nuevo_lider_id')&& $nuevoLiderId =$request->input('nuevo_lider_id')){
                //aqui verificamos si el cliente pertenece a algun grupo
                $pertenece=DB::table('grupos_clientes')
                    ->where('grupo_id',$grupoId)
                    ->where('cliente_id',$nuevoLiderId)
                    ->exists();
                if(!$pertenece){
                  DB::rollBack();
                  return response()->json([
                    'success'=>false,
                    'message'=>'el nuevo lider no pertenece al grupo.'
                  ],422);
                }
                //quitar lider a tods 
                DB::table('grupos_clientes')
                    ->where('grupo_id',$grupoId)
                    ->update(['es_lider'=>false]);
                //asignar lider al nuevo
                DB::table('grupos_clientes')
                    ->where('grupo_id',$grupoId)
                    ->where('cliente_id',$nuevoLiderId)
                    ->update(['es_lider'=>true]);
            }
            $nuevoTotal = DB::table('grupos_clientes')
                ->where('grupo_id', $grupoId)
                ->sum('monto_asignado');

            $reserva->precio_total_viaje = $nuevoTotal;
            $reserva->save();
            $this->pagoService->sincronizarEstadoPagoReserva($reserva->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cambios en integrantes guardados correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los cambios: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateIntegranteFast(Request $request ,$id ){
        // 1. VALIDACIÓN: Solo permitimos los campos reales de tu migración 'clientes'
        $request->validate([
            'campo' => 'required|in:nombres,apellidos,email,telefono,monto_asignado',
            'valor' => 'required|max:250',
            'reserva_id' => 'required|integer|exists:reservas,id'
        ]);

        try {
            $campo = $request->campo;
            
            // 2. Si es monto_asignado, actualizar en grupos_clientes
            if ($campo === 'monto_asignado') {
                // Validar que sea numérico
                if (!is_numeric($request->valor) || $request->valor < 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El monto debe ser un valor numérico válido.'
                    ], 422);
                }

                // Obtener el grupo de la reserva
                $reserva = Reserva::with('reservaGrupo.grupo')->findOrFail($request->reserva_id);
                if (!$reserva->reservaGrupo || !$reserva->reservaGrupo->grupo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reserva grupal no encontrada.'
                    ], 422);
                }

                // Actualizar monto en grupos_clientes
                $grupoCliente = DB::table('grupos_clientes')
                    ->where('grupo_id', $reserva->reservaGrupo->grupo_id)
                    ->where('cliente_id', $id)
                    ->first();

                if (!$grupoCliente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Integrante no encontrado en el grupo.'
                    ], 404);
                }

                DB::table('grupos_clientes')
                    ->where('grupo_id', $reserva->reservaGrupo->grupo_id)
                    ->where('cliente_id', $id)
                    ->update(['monto_asignado' => $request->valor]);

                // Mantener precio_total_viaje consistente con suma de montos asignados del grupo
                $nuevoTotalViaje = DB::table('grupos_clientes')
                    ->where('grupo_id', $reserva->reservaGrupo->grupo_id)
                    ->sum('monto_asignado');

                $reserva->precio_total_viaje = $nuevoTotalViaje;
                $reserva->save();

                // Sincronizar estados después de cambiar el precio total del viaje
                $this->pagoService->sincronizarEstadoPagoReserva($reserva->id);

                // Recargar reserva para estados actualizados
                $reserva->refresh();

                return response()->json([
                    'success' => true,
                    'message' => '¡Monto actualizado correctamente!',
                    'estado' => $reserva->estado,
                    'estado_pago' => $reserva->estado_pago,
                    'precio_total_viaje' => (float) $reserva->precio_total_viaje
                ]);
            }

            // 3. BUSCAR: Localizamos al cliente por el ID enviado desde el JS
            $cliente = Cliente::findOrFail($id);
            
            // 4. REGLA ESPECIAL PARA EMAIL: Evitar duplicados
            if ($campo === 'email') {
                $existe = Cliente::where('email', $request->valor)
                                ->where('id', '!=', $id)
                                ->exists();
                if ($existe) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Este correo electrónico ya está registrado con otro cliente.'
                    ], 422);
                }
            }

            // 5. GUARDADO DINÁMICO PARA CAMPOS DE CLIENTE: 
            // Si $request->campo es 'nombres', esto hace: $cliente->nombres = 'valor'
            $cliente->$campo = $request->valor;
            $cliente->save();

            return response()->json([
                'success' => true,
                'message' => '¡Actualizado con éxito!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro del integrante.'
            ], 404);
        } 
    }
}
