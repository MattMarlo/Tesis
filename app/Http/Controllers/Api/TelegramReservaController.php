<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destino;
use App\Models\PreReserva;
use App\Models\PreReservaIntegrante;
use App\Services\CupoReservaService;
use App\Services\TarifaReservaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TelegramReservaController extends Controller
{
    public function __construct(
        private CupoReservaService $cupos,
        private TarifaReservaService $tarifas
    ) {}

    public function index()
    {
        $destinos = Destino::query()
            ->where('estado_publicacion', 'publicado')
            ->whereNotNull('fecha_salida')
            ->whereDate('fecha_salida', '>=', today())
            ->orderBy('fecha_salida')
            ->get()
            ->map(fn (Destino $destino) => $this->resumen($destino))
            ->filter(fn (array $destino) => $destino['cupos_disponibles'] > 0)
            ->values();

        return response()->json([
            'success' => true,
            'total' => $destinos->count(),
            'destinos' => $destinos,
        ]);
    }

    public function show(Destino $destino)
    {
        $this->validarDisponible($destino);

        return response()->json([
            'success' => true,
            'destino' => [
                ...$this->resumen($destino),
                'etiqueta' => $destino->etiqueta,
                'descripcion_corta' => $destino->descripcion_corta,
                'descripcion' => $destino->descripcion,
                'fecha_regreso' => $destino->fecha_regreso?->format('Y-m-d'),
                'precio_normal' => (float) $destino->precio,
                'precio_promocional' => $destino->precio_promocional
                    ? (float) $destino->precio_promocional : null,
                'dias' => (int) $destino->dias,
                'noches' => (int) ($destino->noches ?? 0),
                'aerolinea' => $destino->aerolinea,
                'hotel' => $destino->hotel,
                'incluye' => $destino->incluye ?? [],
                'no_incluye' => $destino->no_incluye ?? [],
                'itinerario' => $destino->itinerario ?? [],
                'condiciones' => $destino->condiciones,
                'imagen_url' => $destino->imagen
                    ? asset('storage/'.$destino->imagen) : null,
            ],
        ]);
    }

    public function cupos(Request $request)
    {
        $data = $request->validate([
            'destino_id' => ['required', 'integer', 'exists:destinos,id'],
            'cantidad_personas' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $destino = Destino::findOrFail($data['destino_id']);
        $this->validarDisponible($destino);
        $disponibles = $this->cupos->obtenerDisponibles($destino);

        return response()->json([
            'success' => true,
            'disponible' => $data['cantidad_personas'] <= $disponibles,
            'cantidad_solicitada' => $data['cantidad_personas'],
            'cupos_disponibles' => $disponibles,
        ]);
    }

    public function cotizar(Request $request)
    {
        $data = $request->validate([
            'destino_id' => ['required', 'integer', 'exists:destinos,id'],
            'viajeros' => ['required', 'array', 'min:1', 'max:100'],
            'viajeros.*.fecha_nacimiento' => ['required', 'date', 'before:today'],
        ]);
        $destino = Destino::findOrFail($data['destino_id']);
        $this->validarDisponible($destino);
        $this->cupos->validar($destino, count($data['viajeros']));

        return response()->json([
            'success' => true,
            ...$this->crearCotizacion($destino, $data['viajeros']),
        ]);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        if (($input['tipo_reserva'] ?? null) === 'individual' && isset($input['cliente'])) {
            $input['integrantes'] = [$input['cliente']];
            $input['lider_indice'] = 0;
            $input['responsable_pago_indice'] = 0;
        }

        if (! empty($input['referencia_externa'])) {
            $existente = PreReserva::where(
                'referencia_externa',
                $input['referencia_externa']
            )->first();
            if ($existente) {
                return response()->json([
                    'success' => true,
                    'duplicada' => true,
                    'pre_reserva_id' => $existente->id,
                    'estado' => $existente->estado,
                ]);
            }
        }

        $validator = Validator::make($input, $this->reglasPrerreserva());
        $validator->after(function ($validator) use ($input) {
            $this->validarReglasDeNegocio($validator, $input);
        });
        $data = $validator->validate();

        $preReserva = DB::transaction(function () use ($data) {
            $destino = Destino::query()->lockForUpdate()->findOrFail($data['destino_id']);
            $this->validarDisponible($destino);
            $this->cupos->validar($destino, count($data['integrantes']));
            $cotizacion = $this->crearCotizacion($destino, $data['integrantes']);
            $lider = $data['integrantes'][$data['lider_indice']];

            $preReserva = PreReserva::create([
                'cliente_nombre' => trim($lider['nombres'].' '.$lider['apellidos']),
                'email' => isset($lider['email']) ? mb_strtolower(trim($lider['email'])) : null,
                'destino' => $destino->nombre_paquete,
                'destino_id' => $destino->id,
                'telefono' => $lider['telefono'] ?? '',
                'cedula' => $lider['documento'],
                'fecha_viaje' => $destino->fecha_salida,
                'cantidad_personas' => count($data['integrantes']),
                'fecha_reserva' => now(),
                'origen' => PreReserva::ORIGEN_TELEGRAM,
                'telegram_chat_id' => $data['telegram_chat_id'],
                'referencia_externa' => $data['referencia_externa'],
                'estado' => PreReserva::ESTADO_PENDIENTE,
                'tipo_reserva' => $data['tipo_reserva'],
                'tipo_grupo' => $data['tipo_grupo'] ?? null,
                'nombre_grupo' => $data['nombre_grupo'] ?? null,
                'precio_estimado' => $cotizacion['precio_total'],
                'moneda' => $cotizacion['moneda'],
                'acepta_condiciones' => true,
                'confirmada_por_cliente_at' => now(),
            ]);

            foreach ($data['integrantes'] as $index => $integrante) {
                $tarifa = $cotizacion['detalle'][$index];
                $preReserva->integrantes()->create([
                    ...$integrante,
                    'email' => isset($integrante['email'])
                        ? mb_strtolower(trim($integrante['email'])) : null,
                    'es_lider' => $index === $data['lider_indice'],
                    'es_responsable_pago' => isset($data['responsable_pago_indice'])
                        && $index === $data['responsable_pago_indice'],
                    'edad_al_viajar' => $tarifa['edad'],
                    'categoria_tarifa' => $tarifa['categoria'],
                    'porcentaje_tarifa' => $tarifa['porcentaje'],
                    'precio_calculado' => $tarifa['precio_final'],
                ]);
            }

            return $preReserva;
        });

        return response()->json([
            'success' => true,
            'duplicada' => false,
            'message' => 'Prerreserva registrada correctamente.',
            'pre_reserva_id' => $preReserva->id,
            'estado' => $preReserva->estado,
            'precio_estimado' => (float) $preReserva->precio_estimado,
            'moneda' => $preReserva->moneda,
        ], 201);
    }

    private function reglasPrerreserva(): array
    {
        return [
            'tipo_reserva' => ['required', Rule::in(['individual', 'grupal'])],
            'tipo_grupo' => ['nullable', 'required_if:tipo_reserva,grupal', Rule::in(['familiar', 'independiente'])],
            'nombre_grupo' => ['nullable', 'required_if:tipo_reserva,grupal', 'string', 'min:2', 'max:150'],
            'destino_id' => ['required', 'integer', 'exists:destinos,id'],
            'telegram_chat_id' => ['required', 'string', 'max:100'],
            'referencia_externa' => ['required', 'string', 'max:150'],
            'acepta_condiciones' => ['accepted'],
            'lider_indice' => ['required', 'integer', 'min:0'],
            'responsable_pago_indice' => ['nullable', 'integer', 'min:0'],
            'integrantes' => ['required', 'array', 'min:1', 'max:100'],
            'integrantes.*.nombres' => ['required', 'string', 'min:2', 'max:100'],
            'integrantes.*.apellidos' => ['required', 'string', 'min:2', 'max:100'],
            'integrantes.*.tipo_documento' => ['required', Rule::in(['cedula', 'pasaporte'])],
            'integrantes.*.documento' => ['required', 'string', 'min:6', 'max:30', 'distinct'],
            'integrantes.*.fecha_nacimiento' => ['required', 'date', 'before:today'],
            'integrantes.*.fecha_caducidad_documento' => ['nullable', 'date'],
            'integrantes.*.nacionalidad' => ['required', 'string', 'min:2', 'max:80'],
            'integrantes.*.email' => ['nullable', 'email', 'max:150'],
            'integrantes.*.telefono' => ['nullable', 'regex:/^(?:\+593|0)9\d{8}$/'],
            'integrantes.*.contacto_emergencia' => ['nullable', 'string', 'min:2', 'max:150'],
            'integrantes.*.telefono_emergencia' => ['nullable', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
        ];
    }

    private function validarReglasDeNegocio($validator, array $data): void
    {
        $integrantes = $data['integrantes'] ?? [];
        $tipo = $data['tipo_reserva'] ?? null;
        if ($tipo === 'individual' && count($integrantes) !== 1) {
            $validator->errors()->add('integrantes', 'La prerreserva individual debe tener exactamente un viajero.');
        }
        if ($tipo === 'grupal' && count($integrantes) < 2) {
            $validator->errors()->add('integrantes', 'La prerreserva grupal requiere al menos dos viajeros.');
        }
        $liderIndice = $data['lider_indice'] ?? -1;
        if (! array_key_exists($liderIndice, $integrantes)) {
            $validator->errors()->add('lider_indice', 'El líder seleccionado no pertenece al grupo.');
        }
        if (array_key_exists($liderIndice, $integrantes)) {
            $lider = $integrantes[$liderIndice];
            foreach (['email', 'telefono', 'contacto_emergencia', 'telefono_emergencia'] as $campo) {
                if (empty($lider[$campo])) {
                    $validator->errors()->add(
                        "integrantes.$liderIndice.$campo",
                        'Este dato de contacto es obligatorio para el viajero principal.'
                    );
                }
            }
        }
        $responsableIndice = $data['responsable_pago_indice'] ?? null;
        if (($data['tipo_grupo'] ?? null) === 'familiar' && $responsableIndice === null) {
            $validator->errors()->add('responsable_pago_indice', 'El grupo familiar requiere un responsable de pago.');
        } elseif ($responsableIndice !== null && ! array_key_exists($responsableIndice, $integrantes)) {
            $validator->errors()->add('responsable_pago_indice', 'El responsable de pago no pertenece al grupo.');
        }

        $destino = isset($data['destino_id']) ? Destino::find($data['destino_id']) : null;
        foreach ($integrantes as $index => $integrante) {
            if (($integrante['tipo_documento'] ?? null) === 'cedula'
                && ! $this->cedulaEcuatorianaValida((string) ($integrante['documento'] ?? ''))) {
                $validator->errors()->add("integrantes.$index.documento", 'La cédula ecuatoriana no es válida.');
            }
            if (! empty($integrante['documento']) && isset($data['destino_id'])) {
                $duplicado = PreReservaIntegrante::query()
                    ->where('documento', $integrante['documento'])
                    ->whereHas('preReserva', fn ($query) => $query
                        ->where('destino_id', $data['destino_id'])
                        ->whereIn('estado', [
                            PreReserva::ESTADO_PENDIENTE,
                            PreReserva::ESTADO_CONTACTADO,
                            PreReserva::ESTADO_CONVERTIDA,
                        ]))
                    ->exists();
                if ($duplicado) {
                    $validator->errors()->add(
                        "integrantes.$index.documento",
                        'El viajero ya tiene una prerreserva activa para este destino.'
                    );
                }
            }
            if (! $destino || empty($integrante['fecha_nacimiento'])) {
                continue;
            }
            try {
                $tarifa = $this->tarifas->calcularPorFechaNacimiento($integrante['fecha_nacimiento'], $destino);
                if (($index === $liderIndice || $index === $responsableIndice) && $tarifa['edad'] < 18) {
                    $campo = $index === $liderIndice ? 'lider_indice' : 'responsable_pago_indice';
                    $validator->errors()->add($campo, 'El líder y el responsable de pago deben ser mayores de edad.');
                }
                if ($tarifa['edad'] < 18) {
                    $hayAdulto = collect($integrantes)->contains(function ($item) use ($destino) {
                        try {
                            return $this->tarifas->calcularPorFechaNacimiento($item['fecha_nacimiento'], $destino)['edad'] >= 18;
                        } catch (\Throwable) {
                            return false;
                        }
                    });
                    if (! $hayAdulto) {
                        $validator->errors()->add('integrantes', 'Todo menor debe viajar con al menos un adulto.');
                    }
                }
            } catch (\Throwable $e) {
                $validator->errors()->add("integrantes.$index.fecha_nacimiento", $e->getMessage());
            }
            if (! empty($integrante['fecha_caducidad_documento'])
                && Carbon::parse($integrante['fecha_caducidad_documento'])->lt($destino->fecha_salida)) {
                $validator->errors()->add("integrantes.$index.fecha_caducidad_documento", 'El documento debe estar vigente en la fecha del viaje.');
            }
        }
    }

    private function crearCotizacion(Destino $destino, array $viajeros): array
    {
        $detalle = collect($viajeros)->map(function (array $viajero, int $indice) use ($destino) {
            return [
                'viajero' => $indice + 1,
                ...$this->tarifas->calcularPorFechaNacimiento($viajero['fecha_nacimiento'], $destino),
            ];
        })->values();

        return [
            'destino_id' => $destino->id,
            'precio_base' => $this->tarifas->obtenerPrecioBase($destino),
            'moneda' => strtoupper($destino->moneda ?: 'USD'),
            'cantidad_viajeros' => count($viajeros),
            'detalle' => $detalle->all(),
            'precio_total' => round($detalle->sum('precio_final'), 2),
        ];
    }

    private function resumen(Destino $destino): array
    {
        return [
            'id' => $destino->id,
            'nombre_paquete' => $destino->nombre_paquete,
            'pais' => $destino->pais,
            'ciudad_destino' => $destino->ciudad_destino,
            'ciudad_salida' => $destino->ciudad_salida,
            'fecha_salida' => $destino->fecha_salida?->format('Y-m-d'),
            'precio_desde' => $this->tarifas->obtenerPrecioBase($destino),
            'moneda' => strtoupper($destino->moneda ?: 'USD'),
            'cupos_disponibles' => $this->cupos->obtenerDisponibles($destino),
        ];
    }

    private function validarDisponible(Destino $destino): void
    {
        if ($destino->estado_publicacion !== 'publicado'
            || ! $destino->fecha_salida
            || $destino->fecha_salida->lt(today())) {
            throw ValidationException::withMessages([
                'destino_id' => 'El paquete no está publicado o su fecha de salida ya pasó.',
            ]);
        }
    }

    private function cedulaEcuatorianaValida(string $cedula): bool
    {
        if (! preg_match('/^\d{10}$/', $cedula)
            || (int) substr($cedula, 0, 2) < 1
            || (int) substr($cedula, 0, 2) > 24
            || (int) $cedula[2] > 5) {
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
