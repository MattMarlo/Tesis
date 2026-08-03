<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReservaGrupalService
{
    public function __construct(
        private TarifaReservaService $tarifaService,
        private CupoReservaService $cupoService
    ) {
    }

    public function guardar(
        array $datos,
        int $usuarioId
    ): Reserva {
        return DB::transaction(function () use (
            $datos,
            $usuarioId
        ) {
            $destino = Destino::query()
                ->lockForUpdate()
                ->findOrFail($datos['destino_id']);

            if ($destino->estado_publicacion !== 'publicado') {
                throw new InvalidArgumentException(
                    'El paquete seleccionado no está disponible para reservas.'
                );
            }

            if (
                !$destino->fecha_salida ||
                Carbon::parse($destino->fecha_salida)->isPast()
            ) {
                throw new InvalidArgumentException(
                    'La fecha de salida del paquete ya pasó o no está registrada.'
                );
            }

            $this->validarModoCategorias($datos);

            if ($this->solicitaCategoriasFamiliares($datos)) {
                return $this->guardarFamiliarPorCategorias(
                    $datos,
                    $usuarioId,
                    $destino
                );
            }

            $integrantes = collect(
                $datos['integrantes']
            );

            if ($integrantes->count() < 2) {
                throw new InvalidArgumentException(
                    'Una reserva grupal debe tener al menos dos integrantes.'
                );
            }

            $idsIntegrantes = $integrantes
                ->pluck('cliente_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            if (
                $idsIntegrantes->unique()->count() !==
                $idsIntegrantes->count()
            ) {
                throw new InvalidArgumentException(
                    'No puedes agregar el mismo cliente más de una vez.'
                );
            }

            $lideres = $integrantes->filter(
                fn ($integrante) =>
                    !empty($integrante['es_lider'])
            );

            if ($lideres->count() !== 1) {
                throw new InvalidArgumentException(
                    'Selecciona exactamente un líder para el grupo.'
                );
            }

            $liderId = (int) $lideres
                ->first()['cliente_id'];

            $tipoGrupo = $datos['tipo_grupo'];

            $responsablePagoId = null;

            if ($tipoGrupo === Grupo::TIPO_FAMILIAR) {
                $responsablePagoId = (int) (
                    $datos['responsable_pago_id'] ?? 0
                );

                if (
                    !$responsablePagoId ||
                    !$idsIntegrantes->contains(
                        $responsablePagoId
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Selecciona como responsable del pago a un integrante del grupo familiar.'
                    );
                }
            }

            $this->cupoService->validar(
                $destino,
                $integrantes->count()
            );

            $clientes = Cliente::query()
                ->whereIn('id', $idsIntegrantes)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (
                $clientes->count() !==
                $idsIntegrantes->count()
            ) {
                throw new InvalidArgumentException(
                    'Uno o más integrantes no existen.'
                );
            }

            $precioBase =
                $this->tarifaService
                    ->obtenerPrecioBase($destino);

            $detalleIntegrantes = [];
            $precioTotal = 0;

            foreach ($integrantes as $integrante) {
                $clienteId =
                    (int) $integrante['cliente_id'];

                $cliente = $clientes->get(
                    $clienteId
                );

                if (!$cliente->estaActivo()) {
                    throw new InvalidArgumentException(
                        "El cliente {$cliente->nombre_completo} está inactivo."
                    );
                }

                $this->validarReservaDuplicada(
                    $cliente,
                    $destino
                );

                $tarifa =
                    $this->tarifaService->calcular(
                        $cliente,
                        $destino
                    );

                $precioTotal +=
                    $tarifa['precio_final'];

                $detalleIntegrantes[] = [
                    'cliente_id' => $cliente->id,
                    'edad_al_viajar' =>
                        $tarifa['edad'],
                    'categoria_tarifa' =>
                        $tarifa['categoria'],
                    'porcentaje_tarifa' =>
                        $tarifa['porcentaje'],
                    'precio_base' =>
                        $tarifa['precio_base'],
                    'monto_asignado' =>
                        $tarifa['precio_final'],
                    'es_lider' =>
                        $cliente->id === $liderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $detalleLider = collect(
                $detalleIntegrantes
            )->firstWhere(
                'cliente_id',
                $liderId
            );

            if (
                !$detalleLider ||
                (int) $detalleLider['edad_al_viajar'] < 18
            ) {
                throw new InvalidArgumentException(
                    'El líder del grupo debe ser mayor de edad.'
                );
            }

            if ($tipoGrupo === Grupo::TIPO_FAMILIAR) {
                $detalleResponsable = collect(
                    $detalleIntegrantes
                )->firstWhere(
                    'cliente_id',
                    $responsablePagoId
                );

                if (
                    !$detalleResponsable ||
                    (int) $detalleResponsable['edad_al_viajar'] < 18
                ) {
                    throw new InvalidArgumentException(
                        'El responsable del pago familiar debe ser mayor de edad.'
                    );
                }
            }

            $grupo = Grupo::create([
                'nombre_grupo' =>
                    trim($datos['nombre_grupo']),
                'descripcion' =>
                    $tipoGrupo === Grupo::TIPO_FAMILIAR
                        ? 'Reserva de grupo familiar'
                        : 'Reserva de personas independientes',
                'tipo_grupo' => $tipoGrupo,
                'responsable_pago_id' =>
                    $responsablePagoId,
            ]);

            $reserva = Reserva::create([
                'codigo_reserva' =>
                    $this->generarCodigo(),
                'cliente_id' => $liderId,
                'destino_id' => $destino->id,
                'user_id' => $usuarioId,
                'tipo' => Reserva::TIPO_GRUPAL,
                'fecha_reserva' =>
                    now()->toDateString(),
                'fecha_viaje' =>
                    Carbon::parse(
                        $destino->fecha_salida
                    )->toDateString(),
                'precio_total_viaje' =>
                    round($precioTotal, 2),
                'moneda' => strtoupper(
                    $destino->moneda ?: 'USD'
                ),
                'precio_base_persona' =>
                    $precioBase,
                'cantidad_viajeros' =>
                    $integrantes->count(),
                'edad_viajero' => null,
                'categoria_tarifa' => null,
                'porcentaje_tarifa' => null,
                'estado' =>
                    Reserva::ESTADO_PENDIENTE,
                'estado_pago' =>
                    Reserva::PAGO_PENDIENTE,
            ]);

            DB::table('reservas_grupos')->insert([
                'reserva_id' => $reserva->id,
                'grupo_id' => $grupo->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($detalleIntegrantes as &$detalle) {
                $detalle['grupo_id'] = $grupo->id;
            }

            unset($detalle);

            DB::table('grupos_clientes')->insert(
                $detalleIntegrantes
            );

            return $reserva;
        });
    }

    public function actualizar(
        int $reservaId,
        array $datos
    ): Reserva {
        return DB::transaction(function () use (
            $reservaId,
            $datos
        ) {
            $reserva = Reserva::query()
                ->lockForUpdate()
                ->findOrFail($reservaId);

            if (!$reserva->esGrupal()) {
                throw new InvalidArgumentException(
                    'Esta opción solo permite editar reservas grupales.'
                );
            }

            if ($reserva->estaCancelada()) {
                throw new InvalidArgumentException(
                    'Las reservas canceladas no se pueden editar.'
                );
            }

            if (
                $reserva->estado !==
                Reserva::ESTADO_PENDIENTE
            ) {
                throw new InvalidArgumentException(
                    'Solo se pueden editar reservas pendientes.'
                );
            }

            if ($reserva->pagos()->exists()) {
                throw new InvalidArgumentException(
                    'La reserva tiene pagos registrados y no puede cambiarse.'
                );
            }

            $grupoId = DB::table('reservas_grupos')
                ->where('reserva_id', $reserva->id)
                ->value('grupo_id');

            if (!$grupoId) {
                throw new InvalidArgumentException(
                    'La reserva no tiene un grupo asociado.'
                );
            }

            $grupo = Grupo::query()
                ->lockForUpdate()
                ->findOrFail($grupoId);

            $eraFamiliaHistorica =
                $grupo->esFamiliar() &&
                !$grupo->usaCategoriasFamiliares();

            $destino = Destino::query()
                ->lockForUpdate()
                ->findOrFail($datos['destino_id']);

            if (
                $destino->estado_publicacion !==
                'publicado'
            ) {
                throw new InvalidArgumentException(
                    'El paquete seleccionado no está disponible para reservas.'
                );
            }

            if (
                !$destino->fecha_salida ||
                Carbon::parse($destino->fecha_salida)->isPast()
            ) {
                throw new InvalidArgumentException(
                    'La fecha de salida del paquete ya pasó o no está registrada.'
                );
            }

            $this->validarModoCategorias($datos);

            if (
                $eraFamiliaHistorica &&
                (
                    ($datos['tipo_grupo'] ?? null) !==
                        Grupo::TIPO_FAMILIAR ||
                    $this->solicitaCategoriasFamiliares($datos)
                )
            ) {
                throw ValidationException::withMessages([
                    'tipo_grupo' =>
                        'Una familia histórica debe conservar su modalidad por integrantes.',
                ]);
            }

            if ($this->solicitaCategoriasFamiliares($datos)) {
                return $this->actualizarFamiliarPorCategorias(
                    $reserva,
                    $grupo,
                    $destino,
                    $datos
                );
            }

            $integrantes = collect(
                $datos['integrantes']
            );

            if ($integrantes->count() < 2) {
                throw new InvalidArgumentException(
                    'Una reserva grupal debe tener al menos dos integrantes.'
                );
            }

            $idsIntegrantes = $integrantes
                ->pluck('cliente_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            if (
                $idsIntegrantes->unique()->count() !==
                $idsIntegrantes->count()
            ) {
                throw new InvalidArgumentException(
                    'No puedes agregar el mismo cliente más de una vez.'
                );
            }

            $lideres = $integrantes->filter(
                fn ($integrante) =>
                    !empty($integrante['es_lider'])
            );

            if ($lideres->count() !== 1) {
                throw new InvalidArgumentException(
                    'Selecciona exactamente un líder para el grupo.'
                );
            }

            $liderId = (int) $lideres
                ->first()['cliente_id'];

            $tipoGrupo = $datos['tipo_grupo'];
            $responsablePagoId = null;

            if ($tipoGrupo === Grupo::TIPO_FAMILIAR) {
                $responsablePagoId = (int) (
                    $datos['responsable_pago_id'] ?? 0
                );

                if (
                    !$responsablePagoId ||
                    !$idsIntegrantes->contains(
                        $responsablePagoId
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Selecciona como responsable del pago a un integrante del grupo familiar.'
                    );
                }
            }

            $this->cupoService->validar(
                $destino,
                $integrantes->count(),
                $reserva->id
            );

            $clientes = Cliente::query()
                ->whereIn('id', $idsIntegrantes)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (
                $clientes->count() !==
                $idsIntegrantes->count()
            ) {
                throw new InvalidArgumentException(
                    'Uno o más integrantes no existen.'
                );
            }

            $precioBase = $this->tarifaService
                ->obtenerPrecioBase($destino);

            $detalleIntegrantes = [];
            $precioTotal = 0;

            foreach ($integrantes as $integrante) {
                $clienteId =
                    (int) $integrante['cliente_id'];

                $cliente = $clientes->get(
                    $clienteId
                );

                if (!$cliente->estaActivo()) {
                    throw new InvalidArgumentException(
                        "El cliente {$cliente->nombre_completo} está inactivo."
                    );
                }

                $this->validarReservaDuplicada(
                    $cliente,
                    $destino,
                    $reserva->id
                );

                $tarifa = $this->tarifaService->calcular(
                    $cliente,
                    $destino
                );

                $precioTotal +=
                    $tarifa['precio_final'];

                $detalleIntegrantes[] = [
                    'grupo_id' => $grupo->id,
                    'cliente_id' => $cliente->id,
                    'edad_al_viajar' =>
                        $tarifa['edad'],
                    'categoria_tarifa' =>
                        $tarifa['categoria'],
                    'porcentaje_tarifa' =>
                        $tarifa['porcentaje'],
                    'precio_base' =>
                        $tarifa['precio_base'],
                    'monto_asignado' =>
                        $tarifa['precio_final'],
                    'es_lider' =>
                        $cliente->id === $liderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $detalleLider = collect(
                $detalleIntegrantes
            )->firstWhere(
                'cliente_id',
                $liderId
            );

            if (
                !$detalleLider ||
                (int) $detalleLider['edad_al_viajar'] < 18
            ) {
                throw new InvalidArgumentException(
                    'El líder del grupo debe ser mayor de edad.'
                );
            }

            if ($tipoGrupo === Grupo::TIPO_FAMILIAR) {
                $detalleResponsable = collect(
                    $detalleIntegrantes
                )->firstWhere(
                    'cliente_id',
                    $responsablePagoId
                );

                if (
                    !$detalleResponsable ||
                    (int) $detalleResponsable[
                        'edad_al_viajar'
                    ] < 18
                ) {
                    throw new InvalidArgumentException(
                        'El responsable del pago familiar debe ser mayor de edad.'
                    );
                }
            }

            $grupo->update([
                'nombre_grupo' =>
                    trim($datos['nombre_grupo']),
                'descripcion' =>
                    $tipoGrupo === Grupo::TIPO_FAMILIAR
                        ? 'Reserva de grupo familiar'
                        : 'Reserva de personas independientes',
                'tipo_grupo' => $tipoGrupo,
                'responsable_pago_id' =>
                    $responsablePagoId,
                'usa_categorias_familiares' => false,
                'cantidad_infantes' => null,
                'cantidad_ninos' => null,
                'cantidad_adultos' => null,
                'cantidad_adultos_mayores' => null,
            ]);

            $reserva->update([
                'cliente_id' => $liderId,
                'destino_id' => $destino->id,
                'fecha_viaje' => Carbon::parse(
                    $destino->fecha_salida
                )->toDateString(),
                'precio_total_viaje' =>
                    round($precioTotal, 2),
                'moneda' => strtoupper(
                    $destino->moneda ?: 'USD'
                ),
                'precio_base_persona' =>
                    $precioBase,
                'cantidad_viajeros' =>
                    $integrantes->count(),
                'edad_viajero' => null,
                'categoria_tarifa' => null,
                'porcentaje_tarifa' => null,
                'estado_pago' =>
                    Reserva::PAGO_PENDIENTE,
            ]);

            if ($eraFamiliaHistorica) {
                $idsHistoricos = DB::table('grupos_clientes')
                    ->where('grupo_id', $grupo->id)
                    ->pluck('cliente_id')
                    ->map(fn ($id) => (int) $id);

                $idsRecibidos = $idsIntegrantes
                    ->map(fn ($id) => (int) $id);

                if ($idsHistoricos->diff($idsRecibidos)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'integrantes' =>
                            'Una reserva familiar histórica no puede eliminar integrantes mediante esta edición.',
                    ]);
                }
            }

            DB::table('grupos_clientes')
                ->where('grupo_id', $grupo->id)
                ->delete();

            DB::table('grupos_clientes')->insert(
                $detalleIntegrantes
            );

            return $reserva->fresh([
                'grupo.clientes',
                'grupo.responsablePago',
                'destino',
            ]);
        });
    }

    private function guardarFamiliarPorCategorias(
        array $datos,
        int $usuarioId,
        Destino $destino
    ): Reserva {
        [$titular, $tarifaTitular, $calculo] =
            $this->prepararFamiliarPorCategorias(
                $datos,
                $destino
            );

        $this->validarReservaDuplicada(
            $titular,
            $destino
        );

        $this->cupoService->validar(
            $destino,
            $calculo['cantidad_viajeros']
        );

        $grupo = Grupo::create([
            'nombre_grupo' => trim($datos['nombre_grupo']),
            'descripcion' => 'Reserva de grupo familiar por cantidades',
            'tipo_grupo' => Grupo::TIPO_FAMILIAR,
            'responsable_pago_id' => $titular->id,
            'usa_categorias_familiares' => true,
            'cantidad_infantes' => $calculo['cantidad_infantes'],
            'cantidad_ninos' => $calculo['cantidad_ninos'],
            'cantidad_adultos' => $calculo['cantidad_adultos'],
            'cantidad_adultos_mayores' =>
                $calculo['cantidad_adultos_mayores'],
        ]);

        $reserva = Reserva::create([
            'codigo_reserva' => $this->generarCodigo(),
            'cliente_id' => $titular->id,
            'destino_id' => $destino->id,
            'user_id' => $usuarioId,
            'tipo' => Reserva::TIPO_GRUPAL,
            'fecha_reserva' => now()->toDateString(),
            'fecha_viaje' => Carbon::parse(
                $destino->fecha_salida
            )->toDateString(),
            'precio_total_viaje' => $calculo['precio_total'],
            'moneda' => strtoupper($destino->moneda ?: 'USD'),
            'precio_base_persona' => $calculo['precio_base'],
            'cantidad_viajeros' => $calculo['cantidad_viajeros'],
            'edad_viajero' => null,
            'categoria_tarifa' => null,
            'porcentaje_tarifa' => null,
            'estado' => Reserva::ESTADO_PENDIENTE,
            'estado_pago' => Reserva::PAGO_PENDIENTE,
        ]);

        DB::table('reservas_grupos')->insert([
            'reserva_id' => $reserva->id,
            'grupo_id' => $grupo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('grupos_clientes')->insert(
            $this->detalleTitularFamiliar(
                $grupo->id,
                $titular,
                $tarifaTitular,
                $calculo['precio_total']
            )
        );

        return $reserva;
    }

    private function solicitaCategoriasFamiliares(array $datos): bool
    {
        return ($datos['tipo_grupo'] ?? null) ===
                Grupo::TIPO_FAMILIAR &&
            !empty($datos['usa_categorias_familiares']);
    }

    private function validarModoCategorias(array $datos): void
    {
        $tipoFamiliar = ($datos['tipo_grupo'] ?? null) ===
            Grupo::TIPO_FAMILIAR;
        $usaCategorias = !empty(
            $datos['usa_categorias_familiares']
        );

        if ($usaCategorias && !$tipoFamiliar) {
            throw ValidationException::withMessages([
                'tipo_grupo' =>
                    'Las categorías familiares solo pueden utilizarse en un grupo familiar.',
            ]);
        }
    }

    private function actualizarFamiliarPorCategorias(
        Reserva $reserva,
        Grupo $grupo,
        Destino $destino,
        array $datos
    ): Reserva {
        [$titular, $tarifaTitular, $calculo] =
            $this->prepararFamiliarPorCategorias(
                $datos,
                $destino
            );

        $this->validarReservaDuplicada(
            $titular,
            $destino,
            $reserva->id
        );

        $this->cupoService->validar(
            $destino,
            $calculo['cantidad_viajeros'],
            $reserva->id
        );

        $grupo->update([
            'nombre_grupo' => trim($datos['nombre_grupo']),
            'descripcion' => 'Reserva de grupo familiar por cantidades',
            'tipo_grupo' => Grupo::TIPO_FAMILIAR,
            'responsable_pago_id' => $titular->id,
            'usa_categorias_familiares' => true,
            'cantidad_infantes' => $calculo['cantidad_infantes'],
            'cantidad_ninos' => $calculo['cantidad_ninos'],
            'cantidad_adultos' => $calculo['cantidad_adultos'],
            'cantidad_adultos_mayores' =>
                $calculo['cantidad_adultos_mayores'],
        ]);

        $reserva->update([
            'cliente_id' => $titular->id,
            'destino_id' => $destino->id,
            'fecha_viaje' => Carbon::parse(
                $destino->fecha_salida
            )->toDateString(),
            'precio_total_viaje' => $calculo['precio_total'],
            'moneda' => strtoupper($destino->moneda ?: 'USD'),
            'precio_base_persona' => $calculo['precio_base'],
            'cantidad_viajeros' => $calculo['cantidad_viajeros'],
            'edad_viajero' => null,
            'categoria_tarifa' => null,
            'porcentaje_tarifa' => null,
            'estado_pago' => Reserva::PAGO_PENDIENTE,
        ]);

        DB::table('grupos_clientes')
            ->where('grupo_id', $grupo->id)
            ->delete();

        DB::table('grupos_clientes')->insert(
            $this->detalleTitularFamiliar(
                $grupo->id,
                $titular,
                $tarifaTitular,
                $calculo['precio_total']
            )
        );

        return $reserva->fresh([
            'grupo.clientes',
            'grupo.responsablePago',
            'destino',
        ]);
    }

    private function prepararFamiliarPorCategorias(
        array $datos,
        Destino $destino
    ): array {
        $titular = Cliente::query()
            ->lockForUpdate()
            ->findOrFail((int) ($datos['titular_id'] ?? 0));

        if (!$titular->estaActivo()) {
            throw new InvalidArgumentException(
                'El titular seleccionado está inactivo.'
            );
        }

        if (
            !$titular->fecha_nacimiento ||
            !$titular->tipo_documento ||
            !$titular->documento ||
            !$titular->nacionalidad
        ) {
            throw new InvalidArgumentException(
                'El titular debe tener información completa antes de reservar.'
            );
        }

        $tarifaTitular = $this->tarifaService->calcular(
            $titular,
            $destino
        );

        if ((int) $tarifaTitular['edad'] < 18) {
            throw new InvalidArgumentException(
                'El titular del grupo familiar debe ser mayor de edad.'
            );
        }

        $calculo = $this->tarifaService
            ->calcularPorCantidadesFamiliares(
                $destino,
                $datos
            );

        if ($calculo['cantidad_viajeros'] < 2) {
            throw new InvalidArgumentException(
                'Una reserva grupal debe incluir al menos dos viajeros.'
            );
        }

        if (
            (int) $tarifaTitular['edad'] <= 60 &&
            $calculo['cantidad_adultos'] < 1
        ) {
            throw new InvalidArgumentException(
                'Incluye al titular en la cantidad de adultos.'
            );
        }

        if (
            (int) $tarifaTitular['edad'] >= 61 &&
            $calculo['cantidad_adultos_mayores'] < 1
        ) {
            throw new InvalidArgumentException(
                'Incluye al titular en la cantidad de adultos mayores.'
            );
        }

        return [$titular, $tarifaTitular, $calculo];
    }

    private function detalleTitularFamiliar(
        int $grupoId,
        Cliente $titular,
        array $tarifaTitular,
        float $precioTotal
    ): array {
        return [
            'grupo_id' => $grupoId,
            'cliente_id' => $titular->id,
            'edad_al_viajar' => $tarifaTitular['edad'],
            'categoria_tarifa' => $tarifaTitular['categoria'],
            'porcentaje_tarifa' => $tarifaTitular['porcentaje'],
            'precio_base' => $tarifaTitular['precio_base'],
            'monto_asignado' => round($precioTotal, 2),
            'es_lider' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function validarReservaDuplicada(
        Cliente $cliente,
        Destino $destino,
        ?int $reservaIgnoradaId = null
    ): void {
        $estadosActivos = [
            Reserva::ESTADO_PENDIENTE,
            Reserva::ESTADO_CONFIRMADA,
        ];

        $reservaIndividual = Reserva::query()
            ->where('cliente_id', $cliente->id)
            ->where('destino_id', $destino->id)
            ->where('tipo', Reserva::TIPO_INDIVIDUAL)
            ->whereIn('estado', $estadosActivos)
            ->when(
                $reservaIgnoradaId,
                function ($consulta) use ($reservaIgnoradaId) {
                    $consulta->where(
                        'id',
                        '!=',
                        $reservaIgnoradaId
                    );
                }
            )
            ->exists();

        $reservaGrupal = DB::table(
            'reservas as r'
        )
            ->join(
                'reservas_grupos as rg',
                'rg.reserva_id',
                '=',
                'r.id'
            )
            ->join(
                'grupos_clientes as gc',
                'gc.grupo_id',
                '=',
                'rg.grupo_id'
            )
            ->where('gc.cliente_id', $cliente->id)
            ->where('r.destino_id', $destino->id)
            ->whereIn('r.estado', $estadosActivos)
            ->when(
                $reservaIgnoradaId,
                function ($consulta) use ($reservaIgnoradaId) {
                    $consulta->where(
                        'r.id',
                        '!=',
                        $reservaIgnoradaId
                    );
                }
            )
            ->exists();

        if ($reservaIndividual || $reservaGrupal) {
            throw new InvalidArgumentException(
                "{$cliente->nombre_completo} ya tiene una reserva activa para este paquete."
            );
        }
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'RES-' .
                now()->format('Ymd') .
                '-' .
                Str::upper(Str::random(6));
        } while (
            Reserva::where(
                'codigo_reserva',
                $codigo
            )->exists()
        );

        return $codigo;
    }
}
