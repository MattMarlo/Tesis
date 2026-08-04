<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Grupo;
use App\Models\Reserva;
use App\Models\ViajeroReserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ViajeroReservaService
{
    public function __construct(
        private readonly TarifaReservaService $tarifaService
    ) {
    }

    public function sincronizarTitular(Reserva|int $reserva): ViajeroReserva
    {
        $reservaId = $reserva instanceof Reserva ? $reserva->id : $reserva;

        return DB::transaction(function () use ($reservaId) {
            $reserva = Reserva::query()
                ->with(['grupo', 'cliente', 'operacionViaje'])
                ->lockForUpdate()
                ->findOrFail($reservaId);

            $this->validarFamiliaNueva($reserva);
            $cliente = $reserva->cliente;

            if (!$cliente || !$cliente->fecha_nacimiento) {
                throw new InvalidArgumentException(
                    'El titular no tiene una fecha de nacimiento válida.'
                );
            }

            $clasificacion = $this->clasificar(
                (string) $cliente->fecha_nacimiento->format('Y-m-d'),
                $reserva
            );

            $datosDocumento = $this->validarYNormalizarDocumento([
                'tipo_documento' => $cliente->tipo_documento,
                'documento' => $cliente->documento,
            ]);

            $existente = ViajeroReserva::query()
                ->where('reserva_id', $reserva->id)
                ->where(function ($consulta) use ($cliente) {
                    $consulta->where('cliente_id', $cliente->id)
                        ->orWhere('es_titular', true);
                })
                ->lockForUpdate()
                ->first();

            $this->validarCupoCategoria(
                $reserva,
                $clasificacion['categoria'],
                $existente?->id
            );

            $viajero = $existente ?: new ViajeroReserva();
            $viajero->fill([
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id,
                'nombres' => $cliente->nombres,
                'apellidos' => $cliente->apellidos,
                ...$datosDocumento,
                'fecha_nacimiento' => $cliente->fecha_nacimiento,
                'edad_al_viajar' => $clasificacion['edad'],
                'categoria_tarifa' => $clasificacion['categoria'],
                'es_titular' => true,
            ]);
            $viajero->save();

            return $viajero->fresh();
        });
    }

    public function guardar(Reserva|int $reserva, array $datos): ViajeroReserva
    {
        return $this->persistir($reserva, $datos);
    }

    public function actualizar(ViajeroReserva|int $viajero, array $datos): ViajeroReserva
    {
        $viajeroId = $viajero instanceof ViajeroReserva
            ? $viajero->id
            : $viajero;

        return DB::transaction(function () use ($viajeroId, $datos) {
            $viajero = ViajeroReserva::query()
                ->lockForUpdate()
                ->findOrFail($viajeroId);

            if ($viajero->es_titular) {
                throw new InvalidArgumentException(
                    'Los datos del titular deben sincronizarse desde el cliente.'
                );
            }

            return $this->persistir($viajero->reserva_id, $datos, $viajero);
        });
    }

    public function eliminar(ViajeroReserva|int $viajero): void
    {
        $viajeroId = $viajero instanceof ViajeroReserva
            ? $viajero->id
            : $viajero;

        DB::transaction(function () use ($viajeroId) {
            $viajero = ViajeroReserva::query()
                ->with('reserva.operacionViaje')
                ->withCount(['boletos', 'asignacionesHabitacion'])
                ->lockForUpdate()
                ->findOrFail($viajeroId);

            if ($viajero->es_titular) {
                throw new InvalidArgumentException(
                    'El titular de la reserva no puede eliminarse.'
                );
            }

            $this->validarFamiliaNueva($viajero->reserva);

            if ($viajero->boletos_count || $viajero->asignaciones_habitacion_count) {
                throw new InvalidArgumentException(
                    'No se puede eliminar un viajero con boletos o habitaciones asignadas.'
                );
            }

            $viajero->delete();
        });
    }

    public function validarComposicionParaActualizacion(
        Reserva $reserva,
        array $cantidades,
        string $fechaViaje
    ): void {
        $viajeros = $reserva->viajerosReserva()->lockForUpdate()->get();
        $conteo = collect();

        foreach ($viajeros as $viajero) {
            $categoria = $this->tarifaService
                ->clasificarPorFechaNacimiento(
                    $viajero->fecha_nacimiento->format('Y-m-d'),
                    $fechaViaje
                )['categoria'];
            $conteo[$categoria] = (int) ($conteo[$categoria] ?? 0) + 1;
        }

        foreach ($this->limitesCategorias($cantidades) as $categoria => $limite) {
            if ((int) ($conteo[$categoria] ?? 0) > $limite) {
                throw ValidationException::withMessages([
                    'cantidad_viajeros' =>
                        'Las cantidades nuevas no incluyen a todos los viajeros ya identificados. Corrige primero la composición familiar.',
                ]);
            }
        }
    }

    public function validarYNormalizarDocumento(array $datos): array
    {
        $tipo = filled($datos['tipo_documento'] ?? null)
            ? strtolower(trim((string) $datos['tipo_documento']))
            : null;
        $documento = filled($datos['documento'] ?? null)
            ? strtoupper(preg_replace('/\s+/', '', trim((string) $datos['documento'])))
            : null;

        if (($tipo === null) !== ($documento === null)) {
            throw ValidationException::withMessages([
                'documento' =>
                    'El tipo y el número de documento deben registrarse juntos.',
            ]);
        }

        if ($tipo === null) {
            return ['tipo_documento' => null, 'documento' => null];
        }

        if (!in_array($tipo, [Cliente::DOCUMENTO_CEDULA, Cliente::DOCUMENTO_PASAPORTE], true)) {
            throw ValidationException::withMessages([
                'tipo_documento' => 'Selecciona cédula o pasaporte.',
            ]);
        }

        if ($tipo === Cliente::DOCUMENTO_CEDULA && !$this->cedulaValida($documento)) {
            throw ValidationException::withMessages([
                'documento' => 'La cédula ecuatoriana ingresada no es válida.',
            ]);
        }

        if (
            $tipo === Cliente::DOCUMENTO_PASAPORTE &&
            !preg_match('/^[A-Z0-9]{4,20}$/', $documento)
        ) {
            throw ValidationException::withMessages([
                'documento' =>
                    'El pasaporte debe contener entre 4 y 20 caracteres alfanuméricos.',
            ]);
        }

        return ['tipo_documento' => $tipo, 'documento' => $documento];
    }

    private function persistir(
        Reserva|int $reserva,
        array $datos,
        ?ViajeroReserva $viajero = null
    ): ViajeroReserva {
        $reservaId = $reserva instanceof Reserva ? $reserva->id : $reserva;

        return DB::transaction(function () use ($reservaId, $datos, $viajero) {
            $reserva = Reserva::query()
                ->with(['grupo', 'operacionViaje'])
                ->lockForUpdate()
                ->findOrFail($reservaId);
            $this->validarFamiliaNueva($reserva);

            $documento = $this->validarYNormalizarDocumento($datos);
            $clasificacion = $this->clasificar(
                (string) $datos['fecha_nacimiento'],
                $reserva
            );
            $this->validarCupoCategoria(
                $reserva,
                $clasificacion['categoria'],
                $viajero?->id
            );

            if ($documento['documento']) {
                $duplicado = ViajeroReserva::query()
                    ->where('reserva_id', $reserva->id)
                    ->where('documento', $documento['documento'])
                    ->when($viajero, fn ($q) => $q->whereKeyNot($viajero->id))
                    ->lockForUpdate()
                    ->exists();
                if ($duplicado) {
                    throw ValidationException::withMessages([
                        'documento' => 'El documento ya pertenece a otro viajero de esta reserva.',
                    ]);
                }
            }

            $viajero ??= new ViajeroReserva();
            $viajero->fill([
                'reserva_id' => $reserva->id,
                'cliente_id' => null,
                'nombres' => trim((string) $datos['nombres']),
                'apellidos' => trim((string) $datos['apellidos']),
                ...$documento,
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'edad_al_viajar' => $clasificacion['edad'],
                'categoria_tarifa' => $clasificacion['categoria'],
                'es_titular' => false,
            ]);
            $viajero->save();

            return $viajero->fresh();
        });
    }

    private function validarFamiliaNueva(Reserva $reserva): void
    {
        if (!$reserva->grupo?->usaCategoriasFamiliares()) {
            throw new InvalidArgumentException(
                'El seguimiento de viajeros solo aplica a familias por categorías.'
            );
        }

        if ($reserva->estaCancelada()) {
            throw new InvalidArgumentException(
                'No se puede modificar una reserva cancelada.'
            );
        }


        if ($reserva->operacionViaje?->fueNotificada()) {
            throw new InvalidArgumentException(
                'El expediente ya fue notificado y no puede modificarse.'
            );
        }
    }

    private function clasificar(string $nacimiento, Reserva $reserva): array
    {
        return $this->tarifaService->clasificarPorFechaNacimiento(
            $nacimiento,
            $reserva->fecha_viaje->format('Y-m-d')
        );
    }

    private function validarCupoCategoria(
        Reserva $reserva,
        string $categoria,
        ?int $ignorarId = null
    ): void {
        $limites = $this->limitesCategorias($reserva->grupo->composicionFamiliar());
        $registrados = ViajeroReserva::query()
            ->where('reserva_id', $reserva->id)
            ->where('categoria_tarifa', $categoria)
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->lockForUpdate()
            ->count();

        if ($registrados >= ($limites[$categoria] ?? 0)) {
            throw ValidationException::withMessages([
                'fecha_nacimiento' =>
                    'La categoría calculada del viajero no está disponible en la reserva. Corrige primero las cantidades de la reserva.',
            ]);
        }
    }

    private function limitesCategorias(array $cantidades): array
    {
        return [
            Reserva::TARIFA_INFANTE => (int) ($cantidades['cantidad_infantes'] ?? 0),
            Reserva::TARIFA_NINO => (int) ($cantidades['cantidad_ninos'] ?? 0),
            Reserva::TARIFA_ADULTO => (int) ($cantidades['cantidad_adultos'] ?? 0),
            Reserva::TARIFA_ADULTO_MAYOR => (int) ($cantidades['cantidad_adultos_mayores'] ?? 0),
        ];
    }

    private function cedulaValida(string $cedula): bool
    {
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if ($provincia < 1 || $provincia > 24 || (int) $cedula[2] > 5) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = (int) $cedula[$i] * ($i % 2 === 0 ? 2 : 1);
            $suma += $valor > 9 ? $valor - 9 : $valor;
        }

        return ((10 - ($suma % 10)) % 10) === (int) $cedula[9];
    }

}
