<?php

namespace App\Http\Requests;

use App\Models\GestionOperativa;
use App\Models\GestionOperativaViajero;
use App\Models\OperacionViaje;
use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarGestionOperativaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $camposTexto = [
            'nombre',
            'proveedor',
            'contacto',
            'telefono',
            'correo',
            'ubicacion_origen',
            'destino',
            'referencia_confirmacion',
            'observaciones',
        ];

        $datosNormalizados = [];

        foreach ($camposTexto as $campo) {
            if (!$this->has($campo)) {
                continue;
            }

            $valor = trim(
                (string) $this->input($campo)
            );

            $datosNormalizados[$campo] =
                $valor !== ''
                    ? $valor
                    : null;
        }

        $datosAdicionales = collect(
            $this->input(
                'datos_adicionales',
                []
            )
        )
            ->map(function ($valor) {
                if (!is_string($valor)) {
                    return $valor;
                }

                $valor = trim($valor);

                return $valor !== ''
                    ? $valor
                    : null;
            })
            ->all();

        $viajeros = collect(
            $this->input(
                'viajeros',
                []
            )
        )
            ->map(function ($viajero) {
                if (!is_array($viajero)) {
                    return $viajero;
                }

                $camposTextoViajero = [
                    'numero_documento',
                    'asiento',
                    'referencia_individual',
                    'restricciones',
                    'observaciones',
                ];

                foreach (
                    $camposTextoViajero
                    as $campo
                ) {
                    if (
                        !array_key_exists(
                            $campo,
                            $viajero
                        )
                    ) {
                        continue;
                    }

                    $valor = trim(
                        (string) $viajero[$campo]
                    );

                    $viajero[$campo] =
                        $valor !== ''
                            ? $valor
                            : null;
                }

                return $viajero;
            })
            ->values()
            ->all();

        $this->merge(
            array_merge(
                $datosNormalizados,
                [
                    'datos_adicionales' =>
                        $datosAdicionales,

                    'viajeros' =>
                        $viajeros,
                ]
            )
        );
    }

    public function rules(): array
    {
        $reservaId =
            $this->obtenerReservaId();

        $tipo =
            $this->input('tipo');

        $esAlimentacion =
            $tipo === GestionOperativa::TIPO_ALIMENTACION;

        $esTren =
            $tipo === GestionOperativa::TIPO_TREN;

        $validaServicioContextual =
            $esAlimentacion || $esTren;

        $destinoPaquete = $reservaId
            ? Reserva::query()
                ->with('destino')
                ->find($reservaId)
                ?->destino
            : null;

        $inicioPaquete = $destinoPaquete?->fecha_salida
            ?->copy()
            ->startOfDay();

        $finPaquete = $destinoPaquete?->fecha_regreso
            ?->copy()
            ->endOfDay();

        $requiereUbicaciones = in_array(
            $tipo,
            [
                GestionOperativa::TIPO_TREN,
                GestionOperativa::TIPO_TRASLADO,
            ],
            true
        );

        $requiereViajerosIndividuales =
            in_array(
                $tipo,
                [
                    GestionOperativa::TIPO_TREN,
                    GestionOperativa::TIPO_ENTRADA,
                    GestionOperativa::
                        TIPO_ACTIVIDAD_RESERVADA,
                    GestionOperativa::TIPO_SEGURO,
                ],
                true
            );

        $reglasViajeros = [
            Rule::requiredIf(
                $requiereViajerosIndividuales
            ),
            'nullable',
            'array',
        ];

        if ($requiereViajerosIndividuales) {
            $reglasViajeros[] = 'min:1';
        }

        return [
            'tipo' => [
                'required',
                'string',
                Rule::in(
                    GestionOperativa::
                        TIPOS_PERMITIDOS
                ),
            ],

            'nombre' => [
                'required',
                'string',
                ...($validaServicioContextual ? ['min:3', "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),\/-]+$/u"] : []),
                'max:180',
            ],

            'proveedor' => [
                'required',
                'string',
                ...($validaServicioContextual ? ['min:2', "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),\/-]+$/u"] : []),
                'max:150',
            ],

            'contacto' => [
                'nullable',
                'string',
                ...($validaServicioContextual ? ['min:3', "regex:/^[\p{L}][\p{L}\s.'’-]+$/u"] : []),
                'max:150',
            ],

            'telefono' => [
                'nullable',
                'string',
                ...($validaServicioContextual ? ['regex:/^\+?[0-9\s()\-]{7,20}$/'] : []),
                'max:30',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'fecha_hora_inicio' => [
                'required',
                'date',
                ...($validaServicioContextual && $inicioPaquete ? ['after_or_equal:' . $inicioPaquete->toDateTimeString()] : []),
                ...($validaServicioContextual && $finPaquete ? ['before_or_equal:' . $finPaquete->toDateTimeString()] : []),
            ],

            'fecha_hora_fin' => [
                Rule::requiredIf($esTren),
                'nullable',
                'date',
                'after:fecha_hora_inicio',
                ...($validaServicioContextual && $inicioPaquete ? ['after_or_equal:' . $inicioPaquete->toDateTimeString()] : []),
                ...($validaServicioContextual && $finPaquete ? ['before_or_equal:' . $finPaquete->toDateTimeString()] : []),
            ],

            'ubicacion_origen' => [
                Rule::requiredIf(
                    $requiereUbicaciones
                ),
                'nullable',
                'string',
                ...($validaServicioContextual ? ['min:3'] : []),
                'max:180',
                ...($esTren ? ['different:destino'] : []),
            ],

            'destino' => [
                Rule::requiredIf(
                    $requiereUbicaciones
                ),
                'nullable',
                'string',
                ...($esTren ? ['min:3', 'different:ubicacion_origen'] : []),
                'max:180',
            ],

            'cantidad_viajeros' => [
                'required',
                'integer',
                'min:1',
            ],

            'capacidad' => [
                Rule::requiredIf(
                    $tipo ===
                        GestionOperativa::
                            TIPO_TRASLADO
                ),
                'nullable',
                'integer',
                'min:1',
            ],

            'referencia_confirmacion' => [
                Rule::requiredIf(
                    $this->input('estado') ===
                        GestionOperativa::
                            ESTADO_CONFIRMADO
                ),
                'nullable',
                'string',
                ...($validaServicioContextual ? ['min:3'] : []),
                'max:150',
                ...($esTren ? ['regex:/^[A-Z0-9-]+$/i'] : []),
            ],

            'costo_total' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'moneda' => [
                'required',
                'string',
                Rule::in([
                    'USD',
                    'EUR',
                    'PEN',
                ]),
            ],

            'estado' => [
                'required',
                'string',
                Rule::in(
                    GestionOperativa::
                        ESTADOS_PERMITIDOS
                ),
            ],

            'archivo_comprobante' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],

            'observaciones' => [
                'nullable',
                'string',
                ...($validaServicioContextual ? ['min:3'] : []),
                'max:5000',
            ],

            'datos_adicionales' => [
                'nullable',
                'array',
            ],

            'datos_adicionales.empresa_ferroviaria' => [
                Rule::requiredIf($esTren),
                'nullable',
                'string',
                ...($esTren ? ['min:2', "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),\/-]+$/u"] : []),
                'max:150',
            ],

            'datos_adicionales.clase' => [
                'nullable',
                'string',
                ...($esTren ? ['min:2', "regex:/^[\p{L}][\p{L}\p{N}\s.'’\/-]+$/u"] : []),
                'max:100',
            ],

            'datos_adicionales.ruta' => [
                Rule::requiredIf($esTren),
                'nullable',
                'string',
                ...($esTren ? ['min:3'] : []),
                'max:255',
            ],

            'datos_adicionales.tipo_vehiculo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'datos_adicionales.conductor' => [
                'nullable',
                'string',
                'max:150',
            ],

            'datos_adicionales.telefono_conductor' => [
                'nullable',
                'string',
                'max:30',
            ],

            'datos_adicionales.tipo_menu' => [
                'nullable',
                'string',
                ...($esAlimentacion ? ['min:3', "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),\/-]+$/u"] : []),
                'max:150',
            ],

            'datos_adicionales.restricciones_alimentarias' => [
                'nullable',
                'string',
                ...($esAlimentacion ? ['min:3'] : []),
                'max:2000',
            ],

            'datos_adicionales.cobertura_seguro' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'datos_adicionales.numero_poliza' => [
                'nullable',
                'string',
                'max:150',
            ],

            'datos_adicionales.descripcion_servicio' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'datos_adicionales.atraccion' => [
                'nullable', 'string', 'max:180',
            ],

            'datos_adicionales.franja_acceso' => [
                'nullable', 'string', 'max:150',
            ],

            'datos_adicionales.restaurante' => [
                'nullable', 'string', ...($esAlimentacion ? ['min:3'] : []), 'max:180',
            ],

            'datos_adicionales.punto_encuentro' => [
                'nullable', 'string', 'max:180',
            ],

            'datos_adicionales.aseguradora' => [
                'nullable', 'string', 'max:150',
            ],

            'viajeros' =>
                $reglasViajeros,

            'viajeros.*.viajero_reserva_id' => [
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'viajeros_reserva',
                    'id'
                )->where(
                    fn ($consulta) =>
                        $consulta->where(
                            'reserva_id',
                            $reservaId
                        )
                ),
            ],

            'viajeros.*.numero_documento' => [
                'nullable',
                'string',
                ...($esTren ? ['min:3', 'regex:/^[A-Z0-9-]+$/i'] : []),
                'max:150',
            ],

            'viajeros.*.asiento' => [
                'nullable',
                'string',
                ...($esTren ? ['regex:/^[0-9]{1,3}[A-Z]$/i'] : []),
                'max:30',
            ],

            'viajeros.*.referencia_individual' => [
                'nullable',
                'string',
                ...($esTren ? ['min:3', 'regex:/^[A-Z0-9-]+$/i'] : []),
                'max:150',
            ],

            'viajeros.*.estado' => [
                'required',
                'string',

                Rule::in(
                    GestionOperativaViajero::
                        ESTADOS_PERMITIDOS
                ),
            ],

            'viajeros.*.restricciones' => [
                'nullable',
                'string',
                ...($esTren ? ['min:3'] : []),
                'max:2000',
            ],

            'viajeros.*.observaciones' => [
                'nullable',
                'string',
                ...($esTren ? ['min:3'] : []),
                'max:2000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                $this->validarCapacidad(
                    $validator
                );

                $this
                    ->validarDetallesIndividuales(
                        $validator
                    );
            },
        ];
    }

    private function validarCapacidad(
        Validator $validator
    ): void {
        if (
            $this->input('tipo') !==
                GestionOperativa::TIPO_TRASLADO
        ) {
            return;
        }

        $cantidadViajeros = (int)
            $this->input(
                'cantidad_viajeros',
                0
            );

        $capacidad = (int)
            $this->input(
                'capacidad',
                0
            );

        if (
            $capacidad > 0
            && $cantidadViajeros > 0
            && $capacidad < $cantidadViajeros
        ) {
            $validator->errors()->add(
                'capacidad',
                'La capacidad del vehículo no puede ser menor que la cantidad de viajeros.'
            );
        }
    }

    private function validarDetallesIndividuales(
        Validator $validator
    ): void {
        $tipo =
            $this->input('tipo');

        $tiposConDocumentoIndividual = [
            GestionOperativa::TIPO_TREN,
            GestionOperativa::TIPO_ENTRADA,
            GestionOperativa::
                TIPO_ACTIVIDAD_RESERVADA,
            GestionOperativa::TIPO_SEGURO,
        ];

        if (
            !in_array(
                $tipo,
                $tiposConDocumentoIndividual,
                true
            )
        ) {
            return;
        }

        foreach (
            $this->input(
                'viajeros',
                []
            )
            as $indice => $viajero
        ) {
            if (
                ($viajero['estado'] ?? null) !==
                    GestionOperativaViajero::
                        ESTADO_CONFIRMADO
            ) {
                continue;
            }

            $tieneDocumento = filled(
                $viajero['numero_documento']
                ?? null
            );

            $tieneReferencia = filled(
                $viajero[
                    'referencia_individual'
                ] ?? null
            );

            if (
                !$tieneDocumento
                && !$tieneReferencia
            ) {
                $validator->errors()->add(
                    "viajeros.$indice.numero_documento",
                    'Un viajero confirmado debe tener un número de documento o una referencia individual.'
                );
            }
        }
    }

    private function obtenerReservaId(): ?int
    {
        /*
         * Rutas de creación y vinculación:
         * contienen {operacion}.
         */
        $operacion =
            $this->route('operacion');

        if (
            $operacion instanceof
                OperacionViaje
        ) {
            return (int)
                $operacion->reserva_id;
        }

        if (is_numeric($operacion)) {
            $reservaId =
                OperacionViaje::query()
                    ->whereKey($operacion)
                    ->value('reserva_id');

            if ($reservaId) {
                return (int) $reservaId;
            }
        }

        /*
         * Ruta de actualización:
         * contiene {gestion}, pero no {operacion}.
         */
        $gestion =
            $this->route('gestion');

        if (
            $gestion instanceof
                GestionOperativa
        ) {
            return OperacionViaje::query()
                ->whereKey(
                    $gestion
                        ->operacion_viaje_id
                )
                ->value('reserva_id');
        }

        if (is_numeric($gestion)) {
            $operacionId =
                GestionOperativa::query()
                    ->whereKey($gestion)
                    ->value(
                        'operacion_viaje_id'
                    );

            if (!$operacionId) {
                return null;
            }

            return OperacionViaje::query()
                ->whereKey($operacionId)
                ->value('reserva_id');
        }

        return null;
    }

    public function messages(): array
    {
        return [
            'tipo.required' =>
                'Selecciona el tipo de gestión.',

            'tipo.in' =>
                'El tipo de gestión seleccionado no es válido.',

            'nombre.required' =>
                'Ingresa el nombre de la gestión.',

            'nombre.max' =>
                'El nombre no puede superar 180 caracteres.',

            'nombre.min' =>
                'El nombre debe tener al menos tres caracteres.',

            'nombre.regex' =>
                'Ingresa un nombre de servicio válido.',

            'proveedor.required' =>
                'Ingresa el proveedor del servicio.',

            'proveedor.max' =>
                'El proveedor no puede superar 150 caracteres.',

            'proveedor.min' =>
                'El proveedor debe tener al menos dos caracteres.',

            'proveedor.regex' =>
                'Ingresa un proveedor válido.',

            'contacto.min' =>
                'La persona de contacto debe tener al menos tres caracteres.',

            'contacto.regex' =>
                'Ingresa un nombre de contacto válido.',

            'correo.email' =>
                'Ingresa un correo electrónico válido.',

            'fecha_hora_inicio.required' =>
                'Selecciona la fecha y hora de inicio.',

            'fecha_hora_inicio.date' =>
                'La fecha y hora de inicio no es válida.',

            'fecha_hora_fin.after' =>
                'La fecha y hora final debe ser posterior al inicio.',

            'fecha_hora_fin.required' =>
                'Selecciona la fecha y hora de llegada.',

            'fecha_hora_inicio.after_or_equal' =>
                'El inicio no puede ser anterior a la salida del paquete.',

            'fecha_hora_inicio.before_or_equal' =>
                'El inicio no puede superar la fecha de regreso del paquete.',

            'fecha_hora_fin.after_or_equal' =>
                'La finalización no puede ser anterior a la salida del paquete.',

            'fecha_hora_fin.before_or_equal' =>
                'La finalización no puede superar la fecha de regreso del paquete.',

            'telefono.regex' =>
                'Ingresa un teléfono válido de 7 a 20 caracteres.',

            'datos_adicionales.restaurante.min' =>
                'El establecimiento debe tener al menos tres caracteres.',

            'datos_adicionales.tipo_menu.min' =>
                'El tipo de menú debe tener al menos tres caracteres.',

            'datos_adicionales.tipo_menu.regex' =>
                'Ingresa un tipo de menú válido.',

            'datos_adicionales.restricciones_alimentarias.min' =>
                'La restricción debe tener al menos tres caracteres.',

            'ubicacion_origen.required' =>
                'Ingresa el lugar de origen o recogida.',

            'ubicacion_origen.min' =>
                'La ubicación debe tener al menos tres caracteres.',

            'destino.required' =>
                'Ingresa el destino del servicio.',

            'destino.different' =>
                'El destino debe ser diferente al origen.',

            'datos_adicionales.empresa_ferroviaria.required' =>
                'Ingresa la empresa ferroviaria.',

            'datos_adicionales.empresa_ferroviaria.regex' =>
                'Ingresa una empresa ferroviaria válida.',

            'datos_adicionales.ruta.required' =>
                'Ingresa la ruta ferroviaria.',

            'datos_adicionales.ruta.min' =>
                'La ruta debe tener al menos tres caracteres.',

            'datos_adicionales.clase.regex' =>
                'Ingresa una clase ferroviaria válida.',

            'cantidad_viajeros.required' =>
                'Indica la cantidad de viajeros.',

            'cantidad_viajeros.min' =>
                'La gestión debe incluir al menos un viajero.',

            'capacidad.required' =>
                'Indica la capacidad del vehículo.',

            'referencia_confirmacion.required' =>
                'Una gestión confirmada debe tener una referencia de confirmación.',

            'referencia_confirmacion.min' =>
                'La referencia debe tener al menos tres caracteres.',

            'referencia_confirmacion.regex' =>
                'La referencia solo puede contener letras, números y guiones.',

            'observaciones.min' =>
                'Las observaciones deben tener al menos tres caracteres.',

            'costo_total.numeric' =>
                'El costo debe ser un número válido.',

            'costo_total.min' =>
                'El costo no puede ser negativo.',

            'moneda.required' =>
                'Selecciona la moneda.',

            'moneda.in' =>
                'La moneda seleccionada no es válida.',

            'estado.required' =>
                'Selecciona el estado de la gestión.',

            'estado.in' =>
                'El estado seleccionado no es válido.',

            'archivo_comprobante.mimes' =>
                'El comprobante debe ser PDF, JPG, PNG o WEBP.',

            'archivo_comprobante.max' =>
                'El comprobante no puede superar los 5 MB.',

            'viajeros.required' =>
                'Selecciona los viajeros incluidos en esta gestión.',

            'viajeros.min' =>
                'Selecciona al menos un viajero.',

            'viajeros.*.viajero_reserva_id.required' =>
                'Selecciona el viajero.',

            'viajeros.*.viajero_reserva_id.distinct' =>
                'Un viajero no puede repetirse dentro de la misma gestión.',

            'viajeros.*.viajero_reserva_id.exists' =>
                'El viajero seleccionado no pertenece a esta reserva.',

            'viajeros.*.estado.required' =>
                'Selecciona el estado individual del viajero.',

            'viajeros.*.estado.in' =>
                'El estado individual seleccionado no es válido.',

            'viajeros.*.numero_documento.regex' =>
                'El documento o boleto solo puede contener letras, números y guiones.',

            'viajeros.*.asiento.regex' =>
                'Ingresa un asiento válido, por ejemplo 12A.',

            'viajeros.*.referencia_individual.regex' =>
                'La referencia individual solo puede contener letras, números y guiones.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo' =>
                'tipo de gestión',

            'nombre' =>
                'nombre',

            'proveedor' =>
                'proveedor',

            'fecha_hora_inicio' =>
                'fecha y hora de inicio',

            'fecha_hora_fin' =>
                'fecha y hora final',

            'ubicacion_origen' =>
                'ubicación de origen',

            'destino' =>
                'destino',

            'cantidad_viajeros' =>
                'cantidad de viajeros',

            'capacidad' =>
                'capacidad',

            'referencia_confirmacion' =>
                'referencia de confirmación',

            'costo_total' =>
                'costo total',

            'moneda' =>
                'moneda',

            'estado' =>
                'estado',

            'archivo_comprobante' =>
                'comprobante',
        ];
    }
}
