<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class ReservaService
{
    /**
     * Lógica para guardar la reserva INDIVIDUAL
     */
    public function guardarIndividual($datos)
    {
        $codigo_reserva = $this->generarCodigo();
        $fecha_actual = Carbon::now();

        return DB::transaction(function () use ($datos, $codigo_reserva, $fecha_actual) {
            $usuario_id = $datos['user_id'] ?? null;
            //$estado_reserva = $datos['estado'] ?? 'pendiente';
            $estado_reserva = 'pendiente';

            // Determinar el estado inicial del pago
            $monto_depo = $datos['monto_depositado'] ?? 0;
            $precio_total = $datos['precio_total_viaje'];

            //$estado_pago = ($monto_depo > 0) ? 'parcial' : 'pendiente';
            //if ($monto_depo == $precio_total && $precio_total > 0) {
               // $estado_pago = 'pagado';
                //$estado_reserva = 'confirmada';
           // }
           $estados = $this->calcularEstados($monto_depo, $precio_total);
           $estado_reserva = $estados['estado_reserva'];
           $estado_pago = $estados['estado_pago'];
                    


            // 1. Insertar la reserva (SIN grupo_id, ya no existe en esta tabla)
            $reserva_id = DB::table('reservas')->insertGetId([
                'codigo_reserva'     => $codigo_reserva,
                'cliente_id'         => $datos['cliente_id'],
                'destino_id'         => $datos['destino_id'],
                'user_id'            => $usuario_id,
                'tipo'               => 'individual',
                'fecha_reserva'      => $datos['fecha_reserva'],
                'fecha_viaje'        => $datos['fecha_viaje'],
                'precio_total_viaje' => $precio_total,
                'estado'             => $estado_reserva,
                'estado_pago'        => $estado_pago,
                'created_at'         => $fecha_actual,
                'updated_at'         => $fecha_actual
            ]);

            // 2. LÓGICA DEL PRIMER PAGO: Si hay monto, insertamos en la tabla de pagos
            if ($monto_depo > 0) {
                DB::table('pagos')->insert([
                    'reserva_id'       => $reserva_id,
                    'cliente_id'       => $datos['cliente_id'],
                    'user_id'          => $usuario_id,
                    'monto_depositado' => $monto_depo,
                    'fecha_pago'       => $datos['fecha_pago'] ?? $fecha_actual,
                    'metodo_pago'      => strtolower($datos['metodo_pago'] ?? 'efectivo')
                ]);
            }
            return $codigo_reserva;
        });
    }

    /**
     * Lógica para guardar la reserva GRUPAL
     */
    public function guardarGrupal($datos)
    {
        $codigo_reserva = $this->generarCodigo();
        $fecha_actual = Carbon::now();

        return DB::transaction(function () use ($datos, $codigo_reserva, $fecha_actual) {
            $usuario_id = $datos['user_id'] ?? null;
            $precio_total = $datos['precio_total_viaje'];
            
            // 1. Identificar al líder
            $lider_id = null;
            foreach ($datos['integrantes'] as $integrante) {
                if (!empty($integrante['es_lider'])) {
                    $lider_id = $integrante['cliente_id'];
                    break;
                }
            }

            if (!$lider_id && count($datos['integrantes']) > 0) {
                // fallback: si no viene líder explícito, tomamos el primero
                $lider_id = $datos['integrantes'][0]['cliente_id'];
            }

            // 2. Usar grupo existente o Insertar Nuevo Grupo
            
            /* if (!empty($datos['grupo_id'])) {
                $grupo_id = $datos['grupo_id'];
            } else {
                $grupo_id = DB::table('grupos')->insertGetId([
                    'nombre_grupo' => $datos['nombre_grupo'],
                    'descripcion'  => 'Reserva grupal - ' . $datos['nombre_grupo'],
                    'created_at'   => $fecha_actual,
                    'updated_at'   => $fecha_actual
                ]);
            }*/
            // 2. Siempre crear un nuevo grupo (ignorar cualquier grupo_id)
            $grupo_id = DB::table('grupos')->insertGetId([
                'nombre_grupo' => $datos['nombre_grupo'],
                'descripcion'  => 'Reserva grupal - ' . $datos['nombre_grupo'],
                'created_at'   => $fecha_actual,
                'updated_at'   => $fecha_actual
            ]);
            
            // 3. Insertar Reserva Principal (Reservas)
            $reserva_id = DB::table('reservas')->insertGetId([
                'codigo_reserva'     => $codigo_reserva,
                'cliente_id'         => $lider_id,
                'destino_id'         => $datos['destino_id'],
                'user_id'            => $usuario_id,
                'tipo'               => 'grupal',
                'fecha_reserva'      => $datos['fecha_reserva'],
                'fecha_viaje'        => $datos['fecha_viaje'],
                'precio_total_viaje' => $precio_total,
                'estado'             => 'pendiente',
                'estado_pago'        => 'pendiente',
                'created_at'         => $fecha_actual,
                'updated_at'         => $fecha_actual
            ]);

            // 4. Mapear la Reserva con el Grupo (reservas_grupos)
            DB::table('reservas_grupos')->insert([
                'reserva_id' => $reserva_id,
                'grupo_id'   => $grupo_id,
                'created_at' => $fecha_actual,
                'updated_at' => $fecha_actual
            ]);

            // 5. Insertar los integrantes en grupos_clientes
            $integrantes_data = [];
            foreach ($datos['integrantes'] as $integrante) {
                $es_lider_val = !empty($integrante['es_lider']) ? true : false;
                $integrantes_data[] = [
                    'grupo_id'       => $grupo_id,
                    'cliente_id'     => $integrante['cliente_id'],
                    'monto_asignado' => $integrante['monto_asignado'] ?? 0,
                    'es_lider'       => $es_lider_val
                ];
            }
            DB::table('grupos_clientes')->insert($integrantes_data);

            return $codigo_reserva;
        });
    }

    /**
     * Función privada para no repetir código de generación de IDs
     */
    private function generarCodigo()
    {
        return 'RES-' . strtoupper(substr(uniqid(), -6));
    }
    public function calcularEstados($monto_depo, $precio_total)
    {
        if ($monto_depo <= 0) {
            return [
                'estado_reserva' => 'pendiente',
                'estado_pago' => 'pendiente'
            ];
        }

        if ($monto_depo < $precio_total) {
            return [
                'estado_reserva' => 'confirmada',
                'estado_pago' => 'parcial'
            ];
        }

        return [
            'estado_reserva' => 'confirmada',
            'estado_pago' => 'pagado'
        ];
    }
}