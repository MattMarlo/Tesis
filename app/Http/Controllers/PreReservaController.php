<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\PreReserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PreReservaController extends Controller
{
    private function normalizeSearchPattern(
        string $value
    ): string {
        $value = mb_strtolower(
            trim($value)
        );

        $value = preg_replace(
            '/[^\p{L}\p{N}]+/u',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        return '%'.
            str_replace(' ', '%', $value).
            '%';
    }

    private function findDestinoBySearch(
        string $value
    ): ?Destino {
        $pattern =
            $this->normalizeSearchPattern(
                $value
            );

        return Destino::query()
            ->where(
                function ($consulta) use (
                    $pattern
                ) {
                    $consulta
                        ->whereRaw(
                            'LOWER(nombre_paquete) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'LOWER(pais) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'LOWER(ciudad_destino) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'LOWER(etiqueta) LIKE ?',
                            [$pattern]
                        );
                }
            )
            ->first();
    }

    public function storeFromWebhook(
        Request $request
    ) {
        $secretConfigurado =
            config(
                'services.n8n.webhook_secret'
            );

        if ($secretConfigurado) {
            $secretRecibido = (string) $request->header('X-N8N-Webhook-Secret');

            if (! $secretRecibido || ! hash_equals((string) $secretConfigurado, $secretRecibido)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Solicitud no autorizada.',
                    ],
                    401
                );
            }
        }

        $validator = Validator::make(
            $request->only([
                'destino',
                'cliente_nombre',
                'telefono',
                'fecha_viaje',
                'email',
                'cantidad_personas',
                'telegram_chat_id',
                'referencia_externa',
            ]),
            [
                'destino' => [
                    'required',
                    'string',
                    'min:2',
                    'max:180',
                ],

                'cliente_nombre' => [
                    'required',
                    'string',
                    'min:3',
                    'max:150',
                ],

                'telefono' => [
                    'required',
                    'string',
                    'regex:/^\+?[0-9\s\-()]{7,20}$/',
                ],

                'fecha_viaje' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],

                'email' => [
                    'required',
                    'email',
                    'max:150',
                ],

                'cantidad_personas' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'telegram_chat_id' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'referencia_externa' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
            ],
            [
                'destino.required' => 'El destino es obligatorio.',

                'cliente_nombre.required' => 'El nombre del cliente es obligatorio.',

                'telefono.required' => 'El teléfono es obligatorio.',

                'telefono.regex' => 'El teléfono no tiene un formato válido.',

                'fecha_viaje.required' => 'La fecha tentativa es obligatoria.',

                'fecha_viaje.after_or_equal' => 'La fecha del viaje no puede ser anterior a hoy.',

                'email.required' => 'El correo electrónico es obligatorio.',

                'email.email' => 'El correo electrónico no es válido.',

                'cantidad_personas.min' => 'Debe existir al menos un viajero.',

                'cantidad_personas.max' => 'La cantidad de viajeros no puede superar 100.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Revisa la información enviada.',
                    'errors' => $validator->errors(),
                ],
                422
            );
        }

        $data = $validator->validated();

        $destino =
            $this->findDestinoBySearch(
                $data['destino']
            );

        if (! $destino) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'El destino enviado no corresponde a un paquete registrado.',
                ],
                422
            );
        }

        $email = mb_strtolower(
            trim($data['email'])
        );

        $referencia =
            ! empty(
                $data['referencia_externa']
            )
                ? trim(
                    $data[
                        'referencia_externa'
                    ]
                )
                : null;

        if ($referencia) {
            $existente = PreReserva::where(
                'referencia_externa',
                $referencia
            )->first();

            if ($existente) {
                return response()->json([
                    'success' => true,
                    'duplicada' => true,
                    'message' => 'La prerreserva ya estaba registrada.',
                    'pre_reserva_id' => $existente->id,
                ]);
            }
        }

        $duplicadaReciente =
            PreReserva::query()
                ->where(
                    'destino_id',
                    $destino->id
                )
                ->where('email', $email)
                ->whereDate(
                    'fecha_viaje',
                    $data['fecha_viaje']
                )
                ->where(
                    'created_at',
                    '>=',
                    now()->subMinutes(10)
                )
                ->first();

        if ($duplicadaReciente) {
            return response()->json([
                'success' => true,
                'duplicada' => true,
                'message' => 'La prerreserva ya estaba registrada.',
                'pre_reserva_id' => $duplicadaReciente->id,
            ]);
        }

        try {
            $preReserva = DB::transaction(
                function () use (
                    $data,
                    $destino,
                    $email,
                    $referencia
                ) {
                    return PreReserva::create([
                        'cliente_nombre' => trim(
                            $data[
                                'cliente_nombre'
                            ]
                        ),

                        'email' => $email,

                        'destino' => $destino
                            ->nombre_paquete
                            ?: $data['destino'],

                        'destino_id' => $destino->id,

                        'telefono' => trim(
                            $data['telefono']
                        ),

                        'cedula' => '',

                        'fecha_viaje' => $data['fecha_viaje'],

                        'cantidad_personas' => (int) (
                            $data[
                                'cantidad_personas'
                            ] ?? 1
                        ),

                        'fecha_reserva' => now(),

                        'origen' => PreReserva::ORIGEN_TELEGRAM,

                        'telegram_chat_id' => $data[
                                'telegram_chat_id'
                            ] ?? null,

                        'referencia_externa' => $referencia,

                        'estado' => PreReserva::ESTADO_PENDIENTE,

                        'user_id' => null,
                    ]);
                }
            );

            $this->notificarN8n(
                $preReserva
            );

            return response()->json(
                [
                    'success' => true,
                    'duplicada' => false,
                    'message' => 'Prerreserva registrada correctamente.',
                    'pre_reserva_id' => $preReserva->id,
                    'estado' => $preReserva->estado,
                ],
                201
            );
        } catch (\Throwable $error) {
            Log::error(
                'Error al registrar prerreserva',
                [
                    'mensaje' => $error->getMessage(),
                    'referencia_externa' => $referencia,
                ]
            );

            return response()->json(
                [
                    'success' => false,
                    'message' => 'No se pudo registrar la prerreserva.',
                ],
                500
            );
        }
    }

    private function notificarN8n(
        PreReserva $preReserva
    ): void {
        $webhookUrl = config(
            'services.n8n.notification_url'
        );

        if (! $webhookUrl) {
            return;
        }

        try {
            $respuesta = Http::timeout(10)
                ->post(
                    $webhookUrl,
                    [
                        'event' => 'prereserva.creada',

                        'data' => [
                            'id' => $preReserva->id,

                            'cliente_nombre' => $preReserva
                                ->cliente_nombre,

                            'email' => $preReserva->email,

                            'telefono' => $preReserva
                                ->telefono,

                            'destino' => $preReserva
                                ->destino,

                            'fecha_viaje' => $preReserva
                                ->fecha_viaje
                                ?->format(
                                    'Y-m-d'
                                ),

                            'cantidad_personas' => $preReserva
                                ->cantidad_personas,

                            'telegram_chat_id' => $preReserva
                                ->telegram_chat_id,

                            'origen' => $preReserva
                                ->origen,

                            'estado' => $preReserva
                                ->estado,

                            'created_at' => $preReserva
                                ->created_at
                                ?->toIso8601String(),
                        ],
                    ]
                );

            if ($respuesta->failed()) {
                Log::warning(
                    'n8n rechazó la notificación de prerreserva.',
                    [
                        'pre_reserva_id' => $preReserva->id,
                        'estado_http' => $respuesta->status(),
                    ]
                );
            }
        } catch (\Throwable $error) {
            Log::error(
                'No se pudo notificar la prerreserva a n8n.',
                [
                    'pre_reserva_id' => $preReserva->id,
                    'mensaje' => $error->getMessage(),
                ]
            );
        }
    }

    public function checkExistence(
        Request $request
    ) {
        $data = $request->validate([
            'email' => [
                'nullable',
                'email',
            ],

            'cedula' => [
                'nullable',
                'string',
            ],

            'destino' => [
                'required',
                'string',
            ],
        ]);

        $cliente = null;

        if (! empty($data['email'])) {
            $cliente = Cliente::where(
                'email',
                mb_strtolower(
                    trim($data['email'])
                )
            )->first();
        }

        if (
            ! $cliente &&
            ! empty($data['cedula'])
        ) {
            $cliente = Cliente::where(
                'documento',
                $data['cedula']
            )->first();
        }

        $destino =
            $this->findDestinoBySearch(
                $data['destino']
            );

        return response()->json([
            'cliente' => [
                'exists' => (bool) $cliente,

                'data' => $cliente
                        ? [
                            'id' => $cliente->id,
                            'nombres' => $cliente->nombres,
                            'apellidos' => $cliente->apellidos,
                            'email' => $cliente->email,
                            'telefono' => $cliente->telefono,
                        ]
                        : null,
            ],

            'destino' => [
                'exists' => (bool) $destino,

                'data' => $destino
                        ? [
                            'id' => $destino->id,
                            'nombre_paquete' => $destino
                                ->nombre_paquete,
                            'pais' => $destino->pais,
                            'precio' => $destino->precio,
                            'dias' => $destino->dias,
                            'capacidad' => $destino->capacidad,
                        ]
                        : null,
            ],
        ]);
    }

    public function index(
        Request $request
    ) {
        $consulta = PreReserva::query()
            ->with([
                'destinoRelacionado',
                'reserva',
            ]);

        if ($request->filled('buscar')) {
            $buscar = trim(
                $request->buscar
            );

            $consulta->where(
                function ($subconsulta) use (
                    $buscar
                ) {
                    $subconsulta
                        ->where(
                            'cliente_nombre',
                            'like',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'telefono',
                            'like',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'destino',
                            'like',
                            "%{$buscar}%"
                        );
                }
            );
        }

        $estadosValidos = [
            PreReserva::ESTADO_PENDIENTE,
            PreReserva::ESTADO_CONTACTADO,
            PreReserva::ESTADO_CONVERTIDA,
            PreReserva::ESTADO_DESCARTADA,
        ];

        if (
            $request->filled('estado') &&
            in_array(
                $request->estado,
                $estadosValidos,
                true
            )
        ) {
            $consulta->where(
                'estado',
                $request->estado
            );
        }

        $preReservas = $consulta
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'total' => PreReserva::count(),

            'pendientes' => PreReserva::where(
                'estado',
                PreReserva::ESTADO_PENDIENTE
            )->count(),

            'contactadas' => PreReserva::where(
                'estado',
                PreReserva::ESTADO_CONTACTADO
            )->count(),

            'convertidas' => PreReserva::where(
                'estado',
                PreReserva::ESTADO_CONVERTIDA
            )->count(),

            'descartadas' => PreReserva::where(
                'estado',
                PreReserva::ESTADO_DESCARTADA
            )->count(),
        ];

        return view(
            'modules.pre_reservas.index',
            [
                'titulo' => 'Prerreservas',

                'preReservas' => $preReservas,

                'resumen' => $resumen,
            ]
        );
    }

    public function edit(string $id)
    {
        $preReserva = PreReserva::with([
            'destinoRelacionado',
            'integrantes',
        ])->findOrFail($id);

        if (! $preReserva->puedeGestionarse()) {
            return to_route(
                'prereservas.index'
            )->with(
                'error',
                'La prerreserva convertida o descartada ya no puede modificarse.'
            );
        }

        $destinos = Destino::query()
            ->where(
                'estado_publicacion',
                'publicado'
            )
            ->orderBy('nombre_paquete')
            ->get();

        return view(
            'modules.pre_reservas.edit',
            [
                'titulo' => 'Editar prerreserva',

                'preReserva' => $preReserva,

                'destinos' => $destinos,
            ]
        );
    }

    public function update(
        Request $request,
        string $id
    ) {
        $preReserva =
            PreReserva::findOrFail($id);

        if (! $preReserva->puedeGestionarse()) {
            return to_route(
                'prereservas.index'
            )->with(
                'error',
                'La prerreserva ya no puede modificarse.'
            );
        }

        $data = $request->validate(
            [
                'cliente_nombre' => [
                    'required',
                    'string',
                    'min:3',
                    'max:150',
                ],

                'email' => [
                    'required',
                    'email',
                    'max:150',
                ],

                'telefono' => [
                    'required',
                    'regex:/^\+?[0-9\s\-()]{7,20}$/',
                ],

                'cedula' => [
                    'nullable',
                    'digits:10',
                    function (
                        string $attribute,
                        mixed $value,
                        \Closure $fail
                    ) {
                        if (
                            $value &&
                            ! $this->validarCedulaEcuatoriana($value)
                        ) {
                            $fail(
                                'La cédula ecuatoriana ingresada no es válida.'
                            );
                        }
                    },
                ],

                'destino_id' => [
                    'required',
                    'integer',
                    'exists:destinos,id',
                ],

                'fecha_viaje' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],

                'cantidad_personas' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'estado' => [
                    'required',
                    Rule::in([
                        PreReserva::ESTADO_PENDIENTE,

                        PreReserva::ESTADO_CONTACTADO,

                        PreReserva::ESTADO_DESCARTADA,
                    ]),
                ],

                'observaciones' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ],
            [
                'cliente_nombre.required' => 'Ingresa el nombre completo.',

                'email.required' => 'Ingresa el correo electrónico.',

                'email.email' => 'Ingresa un correo válido.',

                'telefono.required' => 'Ingresa el teléfono.',

                'telefono.regex' => 'Ingresa un teléfono válido.',

                'cedula.digits' => 'La cédula debe tener 10 dígitos.',

                'destino_id.required' => 'Selecciona el paquete turístico.',

                'destino_id.exists' => 'El paquete seleccionado no existe.',

                'fecha_viaje.required' => 'Selecciona la fecha tentativa.',

                'fecha_viaje.after_or_equal' => 'La fecha no puede ser anterior a hoy.',

                'cantidad_personas.required' => 'Ingresa la cantidad de personas.',

                'cantidad_personas.min' => 'Debe existir al menos una persona.',

                'cantidad_personas.max' => 'La cantidad no puede superar 100 personas.',

                'estado.in' => 'El estado seleccionado no es válido.',

                'observaciones.max' => 'Las observaciones no pueden superar 2000 caracteres.',
            ]
        );

        $destino = Destino::findOrFail(
            $data['destino_id']
        );

        $estadoAnterior =
            $preReserva->estado;

        $preReserva->update([
            'cliente_nombre' => trim(
                $data['cliente_nombre']
            ),

            'email' => mb_strtolower(
                trim($data['email'])
            ),

            'telefono' => trim($data['telefono']),

            'cedula' => $data['cedula'] ?? '',

            'destino' => $destino->nombre_paquete,

            'destino_id' => $destino->id,

            'fecha_viaje' => $data['fecha_viaje'],

            'cantidad_personas' => (int)
                    $data[
                        'cantidad_personas'
                    ],

            'estado' => $data['estado'],

            'fecha_contacto' => $data['estado'] ===
                    PreReserva::ESTADO_CONTACTADO
                    ? (
                        $preReserva
                            ->fecha_contacto
                        ?: now()
                    )
                    : $preReserva
                        ->fecha_contacto,

            'fecha_descarte' => $data['estado'] ===
                    PreReserva::ESTADO_DESCARTADA
                    ? now()
                    : null,

            'observaciones' => $data['observaciones']
                ?? null,

            'user_id' => Auth::id(),
        ]);

        return to_route(
            'prereservas.index'
        )->with(
            'success',
            $estadoAnterior !==
                $preReserva->estado
                ? 'Estado de la prerreserva actualizado correctamente.'
                : 'Prerreserva actualizada correctamente.'
        );
    }

    public function convertToReserva(
        string $id
    ) {
        $preReserva = PreReserva::with(
            'destinoRelacionado'
        )->findOrFail($id);

        if ($preReserva->estaConvertida()) {
            return to_route(
                'prereservas.index'
            )->with(
                'error',
                'Esta prerreserva ya fue convertida.'
            );
        }

        if ($preReserva->estaDescartada()) {
            return to_route(
                'prereservas.index'
            )->with(
                'error',
                'Una prerreserva descartada no puede convertirse.'
            );
        }

        $destino =
            $preReserva
                ->destinoRelacionado
            ?: $this->findDestinoBySearch(
                $preReserva->destino
            );

        if (! $destino) {
            return to_route(
                'prereservas.index'
            )->with(
                'error',
                'No se encontró el paquete solicitado.'
            );
        }

        $cliente = null;

        // El documento identifica al cliente. El correo o el telefono solo
        // sirven como respaldo cuando la prerreserva no tiene documento.
        if ($preReserva->cedula) {
            $cliente = Cliente::where(
                'documento',
                $preReserva->cedula
            )->first();
        }

        if (
            ! $cliente &&
            ! $preReserva->cedula &&
            $preReserva->email
        ) {
            $cliente = Cliente::where(
                'email',
                mb_strtolower(trim($preReserva->email))
            )->first();
        }

        if (
            ! $cliente &&
            ! $preReserva->cedula &&
            $preReserva->telefono
        ) {
            $telefonoOriginal = trim($preReserva->telefono);
            $soloDigitos = preg_replace('/\D+/', '', $telefonoOriginal);

            $cliente = Cliente::whereIn('telefono', array_unique([
                $telefonoOriginal,
                $soloDigitos,
                '+'.$soloDigitos,
            ]))->first();
        }

        if (! $cliente) {
            return redirect()
                ->route(
                    'clientes.create',
                    [
                        'prereserva_id' => $preReserva->id,

                        'destino_id' => $destino->id,
                    ]
                )
                ->with(
                    'error',
                    'Primero registra los datos completos del cliente.'
                );
        }

        $informacionCompleta =
            $cliente->estaActivo() &&
            ! empty($cliente->documento) &&
            ! empty(
                $cliente->tipo_documento
            ) &&
            ! empty(
                $cliente->fecha_nacimiento
            ) &&
            ! empty(
                $cliente->nacionalidad
            );

        if (! $informacionCompleta) {
            return redirect()
                ->route(
                    'clientes.edit',
                    [
                        'id' => $cliente->id,

                        'prereserva_id' => $preReserva->id,

                        'destino_id' => $destino->id,
                    ]
                )
                ->with(
                    'error',
                    'Completa la información del cliente antes de crear la reserva.'
                );
        }

        $parametros = [
            'cliente_id' => $cliente->id,

            'destino_id' => $destino->id,

            'prereserva_id' => $preReserva->id,
        ];

        if (
            $preReserva
                ->cantidad_personas > 1
        ) {
            $parametros[
                'cantidad_personas'
            ] =
                $preReserva
                    ->cantidad_personas;

            return redirect()->route(
                'reservas_grupal.create',
                $parametros
            );
        }

        return redirect()->route(
            'reservas_individual.create',
            $parametros
        );
    }

    public function destroy(string $id)
    {
        $preReserva =
            PreReserva::findOrFail($id);

        if ($preReserva->estaConvertida()) {
            return back()->with(
                'error',
                'Una prerreserva convertida no puede descartarse.'
            );
        }

        if ($preReserva->estaDescartada()) {
            return back()->with(
                'error',
                'La prerreserva ya fue descartada.'
            );
        }

        $preReserva->update([
            'estado' => PreReserva::ESTADO_DESCARTADA,

            'fecha_descarte' => now(),

            'user_id' => Auth::id(),
        ]);

        return back()->with(
            'success',
            'Prerreserva descartada correctamente.'
        );
    }

    private function validarCedulaEcuatoriana(
        string $cedula
    ): bool {
        if (! preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        $tercerDigito = (int) $cedula[2];

        if (
            $provincia < 1 ||
            $provincia > 24 ||
            $tercerDigito >= 6
        ) {
            return false;
        }

        $coeficientes = [
            2, 1, 2, 1, 2,
            1, 2, 1, 2,
        ];

        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $resultado =
                ((int) $cedula[$i]) *
                $coeficientes[$i];

            if ($resultado >= 10) {
                $resultado -= 9;
            }

            $suma += $resultado;
        }

        $digitoVerificador =
            (10 - ($suma % 10)) % 10;

        return $digitoVerificador ===
            (int) $cedula[9];
    }
}
