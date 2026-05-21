<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Pago;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagoService
{
    public function getMetricasGenerales()
    {
        $totalPagosInfo = DB::table('pagos')
            ->select(DB::raw('SUM(monto_depositado) as total_monto'), DB::raw('COUNT(id) as total_trx'))
            ->first();

        $reservas = DB::table('reservas')
            ->select(DB::raw('SUM(precio_total_viaje) as total_esperado'))
            ->first();

        $totalPagos = $totalPagosInfo?->total_monto ?? 0;
        $totalTrx = $totalPagosInfo?->total_trx ?? 0;
        $totalEsperado = $reservas?->total_esperado ?? 0;

        $cobrado = $totalPagos;
        $tasaCobro = $totalEsperado > 0 ? round(($cobrado / $totalEsperado) * 100) : 0;

        $pendiente = $totalEsperado - $cobrado;
        if ($pendiente < 0) $pendiente = 0;

        $reservasConDeuda = DB::table('reservas')
            ->whereRaw('precio_total_viaje > (SELECT COALESCE(SUM(monto_depositado), 0) FROM pagos WHERE pagos.reserva_id = reservas.id)')
            ->count();

        $sinIniciar = DB::table('reservas')
            ->whereRaw('NOT EXISTS (SELECT 1 FROM pagos WHERE pagos.reserva_id = reservas.id)')
            ->get();
            
        $sinIniciarMonto = $sinIniciar->sum('precio_total_viaje');
        $criticaId = $sinIniciar->first() ? $sinIniciar->first()->id : null;

        return [
            'total_pagos'      => $totalPagos,
            'total_trx'        => $totalTrx,
            'cobrado'          => $cobrado,
            'tasa_cobro'       => $tasaCobro,
            'pendiente'        => $pendiente,
            'reservas_deuda'   => $reservasConDeuda,
            'sin_iniciar_monto'=> $sinIniciarMonto,
            'reserva_critica'  => $criticaId
        ];
    }

    public function getListaReservas($filtros = [])
    {
        // Usamos reservaGrupo para la relación correcta (tabla pivote reservas_grupos)
        $reservas = Reserva::with(['cliente', 'pago', 'reservaGrupo.grupo'])
            ->orderBy('created_at', 'desc')
            ->get();

        $lista = $reservas->map(function ($r) {
            $pagado   = $r->pago->sum('monto_depositado');
            $pendiente = $r->precio_total_viaje - $pagado;
            if ($pendiente < 0) $pendiente = 0;

            $estado_calculado = 'Sin pago';
            $porcentaje = 0;
            
            if ($pagado >= $r->precio_total_viaje && $r->precio_total_viaje > 0) {
                $estado_calculado = 'Completado';
                $porcentaje = 100;
            } elseif ($pagado > 0) {
                $estado_calculado = 'Parcial';
                $porcentaje = $r->precio_total_viaje > 0 ? round(($pagado / $r->precio_total_viaje) * 100) : 0;
            }

            $ultimo_pago = $r->pago->sortByDesc('fecha_pago')->first();

            // Nombre del cliente o grupo usando la relación reservaGrupo->grupo
            if ($r->tipo == 'grupal' && $r->reservaGrupo && $r->reservaGrupo->grupo) {
                $cliente_grupo_nombre = $r->reservaGrupo->grupo->nombre_grupo;
            } else {
                $cliente_grupo_nombre = $r->cliente
                    ? $r->cliente->nombres . ' ' . $r->cliente->apellidos
                    : 'Desconocido';
            }

            return [
                'reserva_id'        => $r->id,
                'codigo_reserva'    => $r->codigo_reserva,
                'tipo'              => $r->tipo,
                'cliente_grupo'     => $cliente_grupo_nombre,
                'pagado'            => $pagado,
                'pendiente'         => $pendiente,
                'precio_total'      => $r->precio_total_viaje,
                'metodo'            => $ultimo_pago ? ucfirst($ultimo_pago->metodo_pago) : '-',
                'fecha_ultimo_pago' => $ultimo_pago ? Carbon::parse($ultimo_pago->fecha_pago)->format('Y-m-d') : '-',
                'estado'            => $estado_calculado,
                'porcentaje'        => $porcentaje,
                'id_ultimo_pago'    => $ultimo_pago ? $ultimo_pago->id : null
            ];
        });

        if (!empty($filtros['estado']) && $filtros['estado'] != 'todos') {
            $estadoFiltro = strtolower($filtros['estado']);
            $lista = $lista->filter(function($row) use ($estadoFiltro) {
                return strtolower($row['estado']) == $estadoFiltro || str_starts_with(strtolower($row['estado']), $estadoFiltro);
            });
        }
        
        if (!empty($filtros['metodo']) && $filtros['metodo'] != 'todos') {
            $metodoFiltro = strtolower($filtros['metodo']);
            $lista = $lista->filter(function($row) use ($metodoFiltro) {
                return strtolower($row['metodo']) == $metodoFiltro;
            });
        }

        return $lista;
    }

    public function getDesgloseGrupal($reserva_id)
    {
        // Usamos la relación reservaGrupo (tabla pivote reservas_grupos) para obtener el grupo
        $reserva = Reserva::with(['reservaGrupo.grupo', 'pago'])->findOrFail($reserva_id);
        
        if ($reserva->tipo !== 'grupal' || !$reserva->reservaGrupo || !$reserva->reservaGrupo->grupo) {
            return [];
        }

        $grupo_id = $reserva->reservaGrupo->grupo_id;
        
        // Obtener los clientes del grupo desde la tabla grupos_clientes
        $integrantes = DB::table('grupos_clientes')
            ->join('clientes', 'grupos_clientes.cliente_id', '=', 'clientes.id')
            ->where('grupos_clientes.grupo_id', $grupo_id)
            ->select(
                'clientes.id',
                'clientes.nombres',
                'clientes.apellidos',
                'grupos_clientes.monto_asignado',
                'grupos_clientes.es_lider'
            )
            ->get();

        $desglose = $integrantes->map(function ($integrante) use ($reserva) {
            $pagos_cliente = $reserva->pago->where('cliente_id', $integrante->id)->sum('monto_depositado');
            $asignado  = $integrante->monto_asignado ?? 0;
            $pendiente = $asignado - $pagos_cliente;
            if ($pendiente < 0) $pendiente = 0;

            $estado = 'Sin pago';
            if ($pagos_cliente >= $asignado && $asignado > 0) {
                $estado = 'Pagado';
            } elseif ($pagos_cliente > 0) {
                $estado = 'Parcial';
            }

            return [
                'cliente_id'     => $integrante->id,
                'nombre_completo'=> $integrante->nombres . ' ' . $integrante->apellidos,
                'es_lider'       => $integrante->es_lider ? true : false,
                'asignado'       => $asignado,
                'pagado'         => $pagos_cliente,
                'pendiente'      => $pendiente,
                'estado'         => $estado
            ];
        });

        return $desglose;
    }

    public function registrarPago($datos)
    {
        return DB::transaction(function () use ($datos) {
            $fecha_actual = Carbon::now();
            $monto = $datos['monto_depositado'];

            $pago_id = DB::table('pagos')->insertGetId([
                'reserva_id'       => $datos['reserva_id'],
                'cliente_id'       => $datos['cliente_id'] ?? null,
                'user_id'          => $datos['user_id'] ?? null,
                'monto_depositado' => $monto,
                'fecha_pago'       => $fecha_actual,
                'metodo_pago'      => strtolower($datos['metodo_pago'] ?? 'efectivo'),
                'referencia'       => $datos['referencia'] ?? null,
            ]);

            $this->sincronizarEstadoPagoReserva((int) $datos['reserva_id']);

            return $pago_id;
        });
    }

    /**
     * Recalcula estado_pago (y confirma la reserva si corresponde) según suma de pagos.
     */
    public function sincronizarEstadoPagoReserva(int $reservaId): void
    {
        $reserva = Reserva::find($reservaId);
        if (!$reserva) {
            return;
        }

        $totalPagado = (float) DB::table('pagos')->where('reserva_id', $reserva->id)->sum('monto_depositado');
        $precio = (float) $reserva->precio_total_viaje;

        if ($totalPagado <= 0) {
            $reserva->estado_pago = 'pendiente';
            $reserva->estado = 'pendiente';
        } elseif ($precio > 0 && $totalPagado >= $precio) {
            $reserva->estado_pago = 'pagado';
            if ($reserva->estado !== 'cancelada') {
                $reserva->estado = 'confirmada';
            }
        } else {
            $reserva->estado_pago = 'parcial';
            $reserva->estado = 'pendiente';
        }

        $reserva->save();
    }

    public function actualizarPago(int $pagoId, array $datos): void
    {
        DB::transaction(function () use ($pagoId, $datos) {
            $pago = Pago::findOrFail($pagoId);
            $updates = [
                'monto_depositado' => $datos['monto_depositado'],
                'metodo_pago'      => strtolower($datos['metodo_pago'] ?? $pago->metodo_pago),
                'referencia'       => $datos['referencia'] ?? null,
            ];
            DB::table('pagos')->where('id', $pagoId)->update($updates);

            $this->sincronizarEstadoPagoReserva((int) $pago->reserva_id);
        });
    }

    public function anularPago(int $pagoId): void
    {
        DB::transaction(function () use ($pagoId) {
            $pago = Pago::findOrFail($pagoId);
            $reservaId = (int) $pago->reserva_id;
            DB::table('pagos')->where('id', $pagoId)->delete();
            $this->sincronizarEstadoPagoReserva($reservaId);
        });
    }

    public function actualizarIntegranteGrupal(int $reservaId, int $clienteId, array $datos): void
    {
        $reserva = Reserva::with('reservaGrupo')->findOrFail($reservaId);
        if ($reserva->tipo !== 'grupal' || !$reserva->reservaGrupo) {
            throw new InvalidArgumentException('La reserva no es grupal.');
        }
        $grupoId = $reserva->reservaGrupo->grupo_id;

        DB::table('clientes')->where('id', $clienteId)->update([
            'nombres'   => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
        ]);

        DB::table('grupos_clientes')
            ->where('grupo_id', $grupoId)
            ->where('cliente_id', $clienteId)
            ->update(['monto_asignado' => $datos['monto_asignado']]);

        // Sincronizar el precio total de la reserva usando los montos asignados actualizados
        $totalAsignado = DB::table('grupos_clientes')
            ->where('grupo_id', $grupoId)
            ->sum('monto_asignado');

        $reserva = Reserva::findOrFail($reservaId);
        $reserva->precio_total_viaje = $totalAsignado;
        $reserva->save();

        // Recalcular estados de pago y reserva luego de ajuste en monto asignado
        $this->sincronizarEstadoPagoReserva($reservaId);
    }
}
