<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $titulo = 'Clientes';

        $query = Cliente::query()
            ->withCount(['reservas', 'pagos', 'grupos']);

        if ($request->filled('documento')) {
            $documento = trim($request->documento);

            $query->where(function ($consulta) use ($documento) {
                $consulta
                    ->where('documento', 'like', "%{$documento}%")
                    ->orWhere('nombres', 'like', "%{$documento}%")
                    ->orWhere('apellidos', 'like', "%{$documento}%")
                    ->orWhere('email', 'like', "%{$documento}%");
            });
        }

        if (
            $request->filled('estado') &&
            in_array($request->estado, ['activo', 'inactivo'], true)
        ) {
            $query->where('estado', $request->estado);
        }

        $clientes = $query
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        return view(
            'modules.clientes.index',
            compact('titulo', 'clientes')
        );
    }

    public function create()
    {
        return view('modules.clientes.create', [
            'titulo' => 'Registrar cliente',
            'cliente' => new Cliente(),
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizarSolicitud($request);

        $datos = $request->validate(
            $this->reglasValidacion($request),
            $this->mensajesValidacion()
        );

        $rutaArchivo = null;

        try {
            if ($request->hasFile('archivo')) {
                $rutaArchivo = $request
                    ->file('archivo')
                    ->store('clientes', 'public');

                $datos['archivo'] = $rutaArchivo;
            }

            $cliente = DB::transaction(function () use ($datos) {
                return Cliente::create($datos);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente registrado correctamente.',
                    'cliente' => [
                        'id' => $cliente->id,
                        'nombres' => $cliente->nombres,
                        'apellidos' => $cliente->apellidos,
                        'nombre_completo' => $cliente->nombre_completo,
                        'email' => $cliente->email,
                        'telefono' => $cliente->telefono,
                        'tipo_documento' => $cliente->tipo_documento,
                        'documento' => $cliente->documento,
                        'estado' => $cliente->estado,
                        'archivo' => $cliente->archivo
                            ? Storage::url($cliente->archivo)
                            : null,
                    ],
                ], 201);
            }

            return to_route('clientes')->with(
                'success',
                'Cliente registrado correctamente.'
            );
        } catch (\Throwable $error) {
            if (
                $rutaArchivo &&
                Storage::disk('public')->exists($rutaArchivo)
            ) {
                Storage::disk('public')->delete($rutaArchivo);
            }

            Log::error('Error al registrar cliente', [
                'mensaje' => $error->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo registrar el cliente.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar el cliente. Inténtalo nuevamente.'
                );
        }
    }

    public function edit(string $id)
    {
        $cliente = Cliente::findOrFail($id);

        return view('modules.clientes.edit', [
            'titulo' => 'Editar cliente',
            'cliente' => $cliente,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $cliente = Cliente::findOrFail($id);

        $this->normalizarSolicitud($request);

        $datos = $request->validate(
            $this->reglasValidacion($request, $cliente),
            $this->mensajesValidacion()
        );

        $archivoAnterior = $cliente->archivo;
        $archivoNuevo = null;

        try {
            if ($request->hasFile('archivo')) {
                $archivoNuevo = $request
                    ->file('archivo')
                    ->store('clientes', 'public');

                $datos['archivo'] = $archivoNuevo;
            } else {
                unset($datos['archivo']);
            }

            DB::transaction(function () use ($cliente, $datos) {
                $cliente->update($datos);
            });

            if (
                $archivoNuevo &&
                $archivoAnterior &&
                $archivoAnterior !== $archivoNuevo &&
                Storage::disk('public')->exists($archivoAnterior)
            ) {
                Storage::disk('public')->delete($archivoAnterior);
            }

            return to_route('clientes')->with(
                'success',
                'La información del cliente fue actualizada.'
            );
        } catch (\Throwable $error) {
            if (
                $archivoNuevo &&
                Storage::disk('public')->exists($archivoNuevo)
            ) {
                Storage::disk('public')->delete($archivoNuevo);
            }

            Log::error('Error al actualizar cliente', [
                'cliente_id' => $cliente->id,
                'mensaje' => $error->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo actualizar el cliente. Inténtalo nuevamente.'
                );
        }
    }

    public function buscarPorDocumento(Request $request)
    {
        $request->merge([
            'documento' => strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    trim((string) $request->documento)
                )
            ),
        ]);

        $request->validate([
            'documento' => [
                'required',
                'string',
                'max:20',
            ],
        ], [
            'documento.required' => 'Ingresa el número de documento.',
            'documento.max' => 'El documento no puede superar 20 caracteres.',
        ]);

        $cliente = Cliente::where(
            'documento',
            $request->documento
        )->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un cliente con ese documento.',
            ], 404);
        }

        if (!$cliente->estaActivo()) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente está inactivo. Actívalo antes de registrar una reserva.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'nombres' => $cliente->nombres,
                'apellidos' => $cliente->apellidos,
                'nombre_completo' => $cliente->nombre_completo,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'tipo_documento' => $cliente->tipo_documento,
                'documento' => $cliente->documento,
            ],
        ]);
    }

    public function destroy(string $id)
    {
        $cliente = Cliente::withCount([
            'reservas',
            'pagos',
            'grupos',
        ])->findOrFail($id);

        if (
            $cliente->reservas_count > 0 ||
            $cliente->pagos_count > 0 ||
            $cliente->grupos_count > 0
        ) {
            return to_route('clientes')->with(
                'error',
                'No puedes eliminar este cliente porque tiene reservas, pagos o viajes grupales registrados. Puedes cambiar su estado a inactivo.'
            );
        }

        $archivo = $cliente->archivo;

        try {
            DB::transaction(function () use ($cliente) {
                $cliente->delete();
            });

            if (
                $archivo &&
                Storage::disk('public')->exists($archivo)
            ) {
                Storage::disk('public')->delete($archivo);
            }

            return to_route('clientes')->with(
                'success',
                'Cliente eliminado correctamente.'
            );
        } catch (\Throwable $error) {
            Log::error('Error al eliminar cliente', [
                'cliente_id' => $cliente->id,
                'mensaje' => $error->getMessage(),
            ]);

            return to_route('clientes')->with(
                'error',
                'No se pudo eliminar el cliente.'
            );
        }
    }

    private function reglasValidacion(
        Request $request,
        ?Cliente $cliente = null
    ): array {
        $idCliente = $cliente?->id;

        $esEcuatoriano = $this->esNacionalidadEcuatoriana(
            $request->nacionalidad
        );

        $reglasDocumento = $request->tipo_documento === 'cedula'
            ? [
                'required',
                'digits:10',
                function ($atributo, $valor, $fallar) {
                    if (!$this->cedulaEcuatorianaValida($valor)) {
                        $fallar(
                            'La cédula ecuatoriana ingresada no es válida.'
                        );
                    }
                },
                Rule::unique('clientes', 'documento')
                    ->ignore($idCliente),
            ]
            : [
                'required',
                'string',
                'min:6',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('clientes', 'documento')
                    ->ignore($idCliente),
            ];

        return [
            'nombres' => [
                'required',
                'string',
                'min:2',
                'max:100',
                "regex:/^[\pL\s'-]+$/u",
            ],
            'apellidos' => [
                'required',
                'string',
                'min:2',
                'max:100',
                "regex:/^[\pL\s'-]+$/u",
            ],
            'tipo_documento' => [
                'required',
                Rule::in(['cedula', 'pasaporte']),
            ],
            'documento' => $reglasDocumento,
            'fecha_nacimiento' => [
                'required',
                'date',
                'before:today',
            ],
            'nacionalidad' => [
                'required',
                'string',
                'min:3',
                'max:80',
                "regex:/^[\pL\s'-]+$/u",
            ],
            'fecha_caducidad_documento' => [
                'nullable',
                'required_if:tipo_documento,pasaporte',
                'date',
                'after:today',
            ],
            'email' => [
                'required',
                'email',
                'max:50',
                Rule::unique('clientes', 'email')
                    ->ignore($idCliente),
            ],
            'telefono' => [
                'required',
                'digits_between:7,15',
                function ($atributo, $valor, $fallar) use ($esEcuatoriano) {
                    if (!$esEcuatoriano) {
                        return;
                    }

                    $formatoLocal = preg_match(
                        '/^09\d{8}$/',
                        $valor
                    );

                    $formatoInternacional = preg_match(
                        '/^5939\d{8}$/',
                        $valor
                    );

                    if (!$formatoLocal && !$formatoInternacional) {
                        $fallar(
                            'El celular ecuatoriano debe tener el formato 09XXXXXXXX o 5939XXXXXXXX.'
                        );
                    }
                },
            ],
            'contacto_emergencia' => [
                'nullable',
                'string',
                'min:3',
                'max:150',
                "regex:/^[\pL\s'-]+$/u",
            ],
            'telefono_emergencia' => [
                'nullable',
                'required_with:contacto_emergencia',
                'digits_between:7,15',
            ],
            'estado' => [
                'required',
                Rule::in(['activo', 'inactivo']),
            ],
            'archivo' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    private function mensajesValidacion(): array
    {
        return [
            'nombres.required' => 'Ingresa los nombres del cliente.',
            'nombres.min' => 'Los nombres deben tener al menos 2 caracteres.',
            'nombres.max' => 'Los nombres no pueden superar 100 caracteres.',
            'nombres.regex' => 'Los nombres solo pueden contener letras.',

            'apellidos.required' => 'Ingresa los apellidos del cliente.',
            'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',
            'apellidos.max' => 'Los apellidos no pueden superar 100 caracteres.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',

            'tipo_documento.required' => 'Selecciona el tipo de documento.',
            'tipo_documento.in' => 'El tipo de documento seleccionado no es válido.',

            'documento.required' => 'Ingresa el número de documento.',
            'documento.digits' => 'La cédula debe contener exactamente 10 números.',
            'documento.min' => 'El pasaporte debe tener al menos 6 caracteres.',
            'documento.max' => 'El documento no puede superar 20 caracteres.',
            'documento.regex' => 'El pasaporte solo puede contener letras, números y guiones.',
            'documento.unique' => 'Ya existe un cliente con este documento.',

            'fecha_nacimiento.required' => 'Ingresa la fecha de nacimiento.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',

            'nacionalidad.required' => 'Ingresa la nacionalidad.',
            'nacionalidad.min' => 'La nacionalidad debe tener al menos 3 caracteres.',
            'nacionalidad.max' => 'La nacionalidad no puede superar 80 caracteres.',
            'nacionalidad.regex' => 'La nacionalidad solo puede contener letras.',

            'fecha_caducidad_documento.required_if' => 'Ingresa la fecha de caducidad del pasaporte.',
            'fecha_caducidad_documento.date' => 'La fecha de caducidad no es válida.',
            'fecha_caducidad_documento.after' => 'El pasaporte debe encontrarse vigente.',

            'email.required' => 'Ingresa el correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no puede superar 50 caracteres.',
            'email.unique' => 'El correo ya pertenece a otro cliente.',

            'telefono.required' => 'Ingresa el número de teléfono.',
            'telefono.digits_between' => 'El teléfono debe contener entre 7 y 15 números.',

            'contacto_emergencia.min' => 'El contacto de emergencia debe tener al menos 3 caracteres.',
            'contacto_emergencia.max' => 'El contacto de emergencia no puede superar 150 caracteres.',
            'contacto_emergencia.regex' => 'El contacto de emergencia solo puede contener letras.',

            'telefono_emergencia.required_with' => 'Ingresa el teléfono del contacto de emergencia.',
            'telefono_emergencia.digits_between' => 'El teléfono de emergencia debe contener entre 7 y 15 números.',

            'estado.required' => 'Selecciona el estado del cliente.',
            'estado.in' => 'El estado seleccionado no es válido.',

            'archivo.file' => 'El documento adjunto no es válido.',
            'archivo.mimes' => 'El documento debe ser PDF, JPG, JPEG o PNG.',
            'archivo.max' => 'El documento no puede superar los 5 MB.',
        ];
    }

    private function normalizarSolicitud(Request $request): void
    {
        $request->merge([
            'nombres' => mb_convert_case(
                trim((string) $request->nombres),
                MB_CASE_TITLE,
                'UTF-8'
            ),
            'apellidos' => mb_convert_case(
                trim((string) $request->apellidos),
                MB_CASE_TITLE,
                'UTF-8'
            ),
            'documento' => strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    trim((string) $request->documento)
                )
            ),
            'email' => mb_strtolower(
                trim((string) $request->email)
            ),
            'telefono' => preg_replace(
                '/\D+/',
                '',
                (string) $request->telefono
            ),
            'nacionalidad' => mb_convert_case(
                trim((string) $request->nacionalidad),
                MB_CASE_TITLE,
                'UTF-8'
            ),
            'contacto_emergencia' => $request->filled(
                'contacto_emergencia'
            )
                ? mb_convert_case(
                    trim((string) $request->contacto_emergencia),
                    MB_CASE_TITLE,
                    'UTF-8'
                )
                : null,
            'telefono_emergencia' => $request->filled(
                'telefono_emergencia'
            )
                ? preg_replace(
                    '/\D+/',
                    '',
                    (string) $request->telefono_emergencia
                )
                : null,
            'fecha_caducidad_documento' =>
                $request->tipo_documento === 'pasaporte'
                    ? $request->fecha_caducidad_documento
                    : null,
        ]);
    }
    private function esNacionalidadEcuatoriana(
        ?string $nacionalidad
    ): bool {
        $valor = mb_strtolower(
            trim((string) $nacionalidad)
        );

        return in_array($valor, [
            'ecuador',
            'ecuatoriana',
            'ecuatoriano',
        ], true);
    }

    private function cedulaEcuatorianaValida(
        ?string $cedula
    ): bool {
        if (!preg_match('/^\d{10}$/', (string) $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        $tercerDigito = (int) $cedula[2];

        $provinciaValida =
            ($provincia >= 1 && $provincia <= 24) ||
            $provincia === 30;

        if (!$provinciaValida || $tercerDigito > 5) {
            return false;
        }

        $suma = 0;

        for ($indice = 0; $indice < 9; $indice++) {
            $digito = (int) $cedula[$indice];

            if ($indice % 2 === 0) {
                $digito *= 2;

                if ($digito > 9) {
                    $digito -= 9;
                }
            }

            $suma += $digito;
        }

        $digitoVerificador =
            (10 - ($suma % 10)) % 10;

        return $digitoVerificador === (int) $cedula[9];
    }
}