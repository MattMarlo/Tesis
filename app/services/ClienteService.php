<?php

namespace App\Services;

use App\Models\Cliente;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClienteService
{
    /**
     * Validar que el cliente no sea duplicado
     * Verifica documento y email únicos
     */
    private function validarClienteDuplicado($datos, $cliente_id = null)
    {
        // Validar que el documento no esté duplicado
        $documento_existe = Cliente::where('documento', $datos['documento'])
            ->when($cliente_id, function ($query) use ($cliente_id) {
                return $query->where('id', '!=', $cliente_id);
            })
            ->exists();

        if ($documento_existe) {
            throw new InvalidArgumentException('El cliente con este documento ya existe en el sistema.');
        }

        // Validar que el email no esté duplicado
        $email_existe = Cliente::where('email', $datos['email'])
            ->when($cliente_id, function ($query) use ($cliente_id) {
                return $query->where('id', '!=', $cliente_id);
            })
            ->exists();

        if ($email_existe) {
            throw new InvalidArgumentException('El email ya está registrado en otro cliente.');
        }
    }

    /**
     * Guardar un nuevo cliente con validaciones
     */
    public function guardarCliente($datos)
    {
        // Validar duplicados
        $this->validarClienteDuplicado($datos);

        // Crear el cliente
        $cliente = new Cliente();
        $cliente->nombres = $datos['nombres'];
        $cliente->apellidos = $datos['apellidos'];
        $cliente->email = $datos['email'];
        $cliente->telefono = $datos['telefono'];
        $cliente->documento = $datos['documento'];
        $cliente->estado = $datos['estado'] ?? 'activo';
        $cliente->archivo   = $datos['archivo'] ?? null;
        $cliente->save();
        
        return $cliente;
    }

    /**
     * Actualizar un cliente con validaciones
     */
    /*public function actualizarCliente($cliente_id, $datos)
    {
        // Validar duplicados (excluyendo el cliente actual)
        $this->validarClienteDuplicado($datos, $cliente_id);

        $cliente = Cliente::find($cliente_id);
        if (!$cliente) {
            throw new InvalidArgumentException('El cliente no existe.');
        }

        $cliente->nombres = $datos['nombres'];
        $cliente->apellidos = $datos['apellidos'];
        $cliente->email = $datos['email'];
        $cliente->telefono = $datos['telefono'];
        $cliente->documento = $datos['documento'];
        $cliente->estado = $datos['estado'] ?? 'activo';

        if (array_key_exists('archivo', $datos) && $datos['archivo']) {
            $cliente->archivo = $datos['archivo'];
        }

        $cliente->save();

        return $cliente;
    }*/
    /**
 * Actualizar un cliente con validaciones
 */
    public function actualizarCliente($cliente_id, $datos)
    {
        // ============================================================
        // LOG DE ENTRADA (para depurar)
        // ============================================================
        Log::info('🔍 SERVICIO - Inicio actualizarCliente', [
            'cliente_id' => $cliente_id,
            'datos'      => $datos
        ]);

        // ============================================================
        // VALIDAR DUPLICADOS (documento y email)
        // ============================================================
        $this->validarClienteDuplicado($datos, $cliente_id);

        // ============================================================
        // BUSCAR EL CLIENTE
        // ============================================================
        $cliente = Cliente::find($cliente_id);
        if (!$cliente) {
            throw new InvalidArgumentException('El cliente no existe.');
        }

        Log::info('🔍 SERVICIO - Cliente encontrado', [
            'id'            => $cliente->id,
            'archivo_actual' => $cliente->archivo
        ]);

        // ============================================================
        // ASIGNAR CAMPOS BÁSICOS (siempre se actualizan)
        // ============================================================
        $cliente->nombres   = $datos['nombres'];
        $cliente->apellidos = $datos['apellidos'];
        $cliente->email     = $datos['email'];
        $cliente->telefono  = $datos['telefono'];
        $cliente->documento = $datos['documento'];
        $cliente->estado    = $datos['estado'] ?? 'activo';

        // ============================================================
        // ACTUALIZAR ARCHIVO (SOLO si la clave existe en $datos)
        // ============================================================
        //  Siempre que exista la clave 'archivo', la asignamos (puede ser null o ruta)
        if (array_key_exists('archivo', $datos)) {
            $cliente->archivo = $datos['archivo'];
            Log::info('✅ SERVICIO - Archivo asignado', [
                'archivo' => $cliente->archivo
            ]);
        } else {
            Log::info('ℹ️ SERVICIO - No se pasó la clave archivo, se mantiene el valor actual');
        }

        // ============================================================
        // GUARDAR EN BASE DE DATOS
        // ============================================================
        Log::info('🔍 SERVICIO - Antes de save()', [
            'archivo' => $cliente->archivo
        ]);

        $cliente->save();

        Log::info('✅ SERVICIO - Después de save()', [
            'id'      => $cliente->id,
            'archivo' => $cliente->archivo
        ]);

        // ============================================================
        // DEVOLVER EL CLIENTE ACTUALIZADO
        // ============================================================
        return $cliente;
    }
}
