<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DestinoController extends Controller
{
    /**
     * Muestra todos los paquetes turísticos.
     */
    public function index()
    {
        $titulo = 'Paquetes turísticos';

        $destinos = Destino::orderByDesc('created_at')->get();

        return view(
            'modules.destinos.index',
            compact('titulo', 'destinos')
        );
    }

    /**
     * Muestra el formulario para crear un paquete turístico.
     */
    public function create()
    {
        $titulo = 'Crear paquete turístico';

        return view(
            'modules.destinos.create',
            compact('titulo')
        );
    }

    /**
     * Registra un paquete turístico.
     */
    public function store(Request $request)
    {
        $datos = $this->validarDatos($request, true);

        $datos['slug'] = $this->generarSlugUnico(
            $datos['nombre_paquete']
        );

        $datos['destacado'] = $request->boolean('destacado');

        $datos['incluye'] = $this->limpiarLista(
            $datos['incluye'] ?? []
        );

        $datos['no_incluye'] = $this->limpiarLista(
            $datos['no_incluye'] ?? []
        );

        $datos['itinerario'] = $this->limpiarItinerario(
            $datos['itinerario'] ?? []
        );

        $rutaImagen = null;

        try {
            $rutaImagen = $request
                ->file('imagen')
                ->store('destinos', 'public');

            $datos['imagen'] = $rutaImagen;

            Destino::create($datos);

            return to_route('destinos')->with(
                'success',
                'El paquete turístico se registró correctamente.'
            );
        } catch (Exception $e) {
            report($e);

            if (
                $rutaImagen &&
                Storage::disk('public')->exists($rutaImagen)
            ) {
                Storage::disk('public')->delete($rutaImagen);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar el paquete turístico.'
                );
        }
    }

    /**
     * Muestra el formulario para editar un paquete.
     */
    public function edit(string $id)
    {
        $titulo = 'Editar paquete turístico';

        /*
         * Se conserva el nombre $destinos para mantener compatibilidad
         * con la vista de edición que ya utiliza esa variable.
         */
        $destinos = Destino::findOrFail($id);

        return view(
            'modules.destinos.edit',
            compact('titulo', 'destinos')
        );
    }

    /**
     * Actualiza un paquete turístico.
     */
    public function update(Request $request, string $id)
    {
        $destino = Destino::findOrFail($id);

        $datos = $this->validarDatos($request, false);

        $datos['slug'] = $this->generarSlugUnico(
            $datos['nombre_paquete'],
            $destino->id
        );

        $datos['destacado'] = $request->boolean('destacado');

        $datos['incluye'] = $this->limpiarLista(
            $datos['incluye'] ?? []
        );

        $datos['no_incluye'] = $this->limpiarLista(
            $datos['no_incluye'] ?? []
        );

        $datos['itinerario'] = $this->limpiarItinerario(
            $datos['itinerario'] ?? []
        );

        unset($datos['imagen']);

        $imagenAnterior = $destino->imagen;
        $imagenNueva = null;

        try {
            if ($request->hasFile('imagen')) {
                $imagenNueva = $request
                    ->file('imagen')
                    ->store('destinos', 'public');

                $datos['imagen'] = $imagenNueva;
            }

            $destino->update($datos);

            if (
                $imagenNueva &&
                $imagenAnterior &&
                Storage::disk('public')->exists($imagenAnterior)
            ) {
                Storage::disk('public')->delete($imagenAnterior);
            }

            return to_route('destinos')->with(
                'success',
                'El paquete turístico se actualizó correctamente.'
            );
        } catch (Exception $e) {
            report($e);

            if (
                $imagenNueva &&
                Storage::disk('public')->exists($imagenNueva)
            ) {
                Storage::disk('public')->delete($imagenNueva);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo actualizar el paquete turístico.'
                );
        }
    }

    /**
     * Elimina un paquete que no tenga reservas asociadas.
     */
    public function destroy(string $id)
    {
        $destino = Destino::findOrFail($id);

        if ($destino->reservas()->exists()) {
            return to_route('destinos')->with(
                'error',
                'El paquete no puede eliminarse porque tiene reservas asociadas.'
            );
        }

        try {
            $imagen = $destino->imagen;

            $destino->delete();

            if (
                $imagen &&
                Storage::disk('public')->exists($imagen)
            ) {
                Storage::disk('public')->delete($imagen);
            }

            return to_route('destinos')->with(
                'success',
                'El paquete turístico se eliminó correctamente.'
            );
        } catch (Exception $e) {
            report($e);

            return to_route('destinos')->with(
                'error',
                'No se pudo eliminar el paquete turístico.'
            );
        }
    }

    /**
     * Muestra el detalle público de un paquete publicado.
     */
    public function detalle(string $slug)
    {
        $destino = Destino::where('slug', $slug)
            ->where('estado_publicacion', 'publicado')
            ->firstOrFail();

        return view(
            'paquetes.detalle',
            compact('destino')
        );
    }

    /**
     * Valida los datos enviados desde crear y editar.
     */
    private function validarDatos(
        Request $request,
        bool $esCreacion
    ): array {
        /*
         * Se eliminan primero las filas vacías de las listas dinámicas.
         * Así no producen errores los inputs vacíos agregados por JS.
         */
        $request->merge([
            'incluye' => $this->limpiarLista(
                $request->input('incluye', [])
            ),

            'no_incluye' => $this->limpiarLista(
                $request->input('no_incluye', [])
            ),
        ]);

        $datos = $request->validate(
            [
                'nombre_paquete' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'etiqueta' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'pais' => [
                    'required',
                    'string',
                    'max:100',
                    "regex:/^[\\pL\\s.'’-]+$/u",
                ],

                'ciudad_destino' => [
                    'required',
                    'string',
                    'max:100',
                    "regex:/^[\\pL\\s.'’-]+$/u",
                ],

                'categoria' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'descripcion_corta' => [
                    'required',
                    'string',
                    'max:300',
                ],

                'descripcion' => [
                    'required',
                    'string',
                ],

                'ciudad_salida' => [
                    'required',
                    'string',
                    'max:150',
                    "regex:/^[\\pL\\s.'’-]+$/u",
                ],

                'fecha_salida' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],

                'fecha_regreso' => [
                    'required',
                    'date',
                    'after_or_equal:fecha_salida',
                ],

                'precio' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],

                'moneda' => [
                    'required',
                    'in:USD,EUR',
                ],

                'precio_promocional' => [
                    'nullable',
                    'numeric',
                    'min:0.01',
                    'lt:precio',
                ],

                'dias' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:365',
                ],

                'noches' => [
                    'required',
                    'integer',
                    'min:0',
                    'lte:dias',
                ],

                /*
                 * El sistema manejará paquetes con transporte aéreo.
                 * La aerolínea permanece nullable porque puede confirmarse
                 * posteriormente durante la preparación del viaje.
                 */
                'aerolinea' => [
                    'nullable',
                    'string',
                    'max:120',
                    'regex:/\\pL/u',
                ],

                'hotel' => [
                    'nullable',
                    'string',
                    'max:150',
                    'regex:/\\pL/u',
                ],

                'capacidad' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'incluye' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'incluye.*' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'no_incluye' => [
                    'nullable',
                    'array',
                ],

                'no_incluye.*' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                /*
                 * Itinerario común del paquete.
                 */
                'itinerario' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'itinerario.*.dia' => [
                    'required',
                    'integer',
                    'min:1',
                    'lte:dias',
                    'distinct',
                ],

                'itinerario.*.titulo' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'itinerario.*.descripcion' => [
                    'required',
                    'string',
                ],

                'itinerario.*.actividades' => [
                    'nullable',
                    'array',
                ],

                /*
                 * Cada actividad conserva un UUID estable para poder
                 * relacionarla posteriormente con tareas de preparación.
                 */
                'itinerario.*.actividades.*.uuid' => [
                    'nullable',
                    'uuid',
                ],

                'itinerario.*.actividades.*.nombre' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'itinerario.*.actividades.*.descripcion' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                /*
                 * Los horarios son opcionales, como se acordó.
                 */
                'itinerario.*.actividades.*.hora_inicio' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'itinerario.*.actividades.*.hora_fin' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'itinerario.*.actividades.*.ubicacion' => [
                    'nullable',
                    'string',
                    'max:180',
                ],

                /*
                 * Solo se generarán tareas cuando la actividad
                 * realmente requiera gestión.
                 */
                'itinerario.*.actividades.*.requiere_gestion' => [
                    'nullable',
                    'boolean',
                ],

                'itinerario.*.actividades.*.tipo_gestion' => [
                    'nullable',
                    'in:reserva,entrada,guia,alimentacion,alojamiento,actividad,otro',
                ],

                'condiciones' => [
                    'nullable',
                    'string',
                ],

                'estado_publicacion' => [
                    'required',
                    'in:borrador,publicado,no_disponible',
                ],

                'destacado' => [
                    'nullable',
                    'boolean',
                ],

                'imagen' => [
                    $esCreacion ? 'required' : 'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120',
                ],
            ],
            [
                'nombre_paquete.required' =>
                    'El nombre del paquete es obligatorio.',

                'etiqueta.required' =>
                    'La etiqueta promocional es obligatoria.',

                'pais.required' =>
                    'El país de destino es obligatorio.',

                'pais.regex' =>
                    'El país de destino solo puede contener letras.',

                'ciudad_destino.required' =>
                    'La ciudad de destino es obligatoria.',

                'ciudad_destino.regex' =>
                    'La ciudad de destino solo puede contener letras.',

                'categoria.required' =>
                    'Selecciona una categoría.',

                'descripcion_corta.required' =>
                    'La descripción corta es obligatoria.',

                'descripcion_corta.max' =>
                    'La descripción corta no debe superar 300 caracteres.',

                'descripcion.required' =>
                    'La descripción completa es obligatoria.',

                'ciudad_salida.required' =>
                    'La ciudad de salida es obligatoria.',

                'ciudad_salida.regex' =>
                    'La ciudad de salida solo puede contener letras.',

                'fecha_salida.required' =>
                    'La fecha de salida es obligatoria.',

                'fecha_salida.after_or_equal' =>
                    'La fecha de salida no puede ser anterior a hoy.',

                'fecha_regreso.required' =>
                    'La fecha de regreso es obligatoria.',

                'fecha_regreso.after_or_equal' =>
                    'La fecha de regreso no puede ser anterior a la salida.',

                'precio.required' =>
                    'El precio del paquete es obligatorio.',

                'precio.numeric' =>
                    'El precio del paquete debe ser un número válido.',

                'precio_promocional.lt' =>
                    'El precio promocional debe ser menor al precio normal.',

                'dias.required' =>
                    'La duración en días es obligatoria.',

                'noches.required' =>
                    'La duración en noches es obligatoria.',

                'noches.lte' =>
                    'La cantidad de noches no puede superar los días.',

                'aerolinea.regex' =>
                    'La aerolínea debe incluir letras.',

                'hotel.regex' =>
                    'El hotel o alojamiento debe incluir letras.',

                'capacidad.required' =>
                    'La capacidad del paquete es obligatoria.',

                'incluye.required' =>
                    'Agrega al menos un servicio incluido.',

                'incluye.min' =>
                    'Agrega al menos un servicio incluido.',

                'incluye.*.required' =>
                    'Completa o elimina los servicios incluidos que estén vacíos.',

                'itinerario.required' =>
                    'Agrega al menos un día al itinerario.',

                'itinerario.min' =>
                    'Agrega al menos un día al itinerario.',

                'itinerario.*.dia.required' =>
                    'Indica el número de día del itinerario.',

                'itinerario.*.dia.lte' =>
                    'El día del itinerario no puede superar la duración del paquete.',

                'itinerario.*.dia.distinct' =>
                    'No pueden existir días repetidos en el itinerario.',

                'itinerario.*.titulo.required' =>
                    'El título del día es obligatorio.',

                'itinerario.*.descripcion.required' =>
                    'La descripción del día es obligatoria.',

                'itinerario.*.actividades.*.uuid.uuid' =>
                    'El identificador de la actividad no es válido.',

                'itinerario.*.actividades.*.nombre.required' =>
                    'El nombre de la actividad es obligatorio.',

                'itinerario.*.actividades.*.nombre.max' =>
                    'El nombre de la actividad no debe superar 150 caracteres.',

                'itinerario.*.actividades.*.hora_inicio.date_format' =>
                    'La hora de inicio debe tener un formato válido.',

                'itinerario.*.actividades.*.hora_fin.date_format' =>
                    'La hora de finalización debe tener un formato válido.',

                'itinerario.*.actividades.*.ubicacion.max' =>
                    'La ubicación no debe superar 180 caracteres.',

                'itinerario.*.actividades.*.tipo_gestion.in' =>
                    'El tipo de gestión seleccionado no es válido.',

                'estado_publicacion.required' =>
                    'Selecciona el estado de publicación.',

                'estado_publicacion.in' =>
                    'El estado de publicación seleccionado no es válido.',

                'imagen.required' =>
                    'La imagen principal es obligatoria.',

                'imagen.image' =>
                    'El archivo seleccionado debe ser una imagen.',

                'imagen.mimes' =>
                    'La imagen debe estar en formato JPG, PNG o WEBP.',

                'imagen.max' =>
                    'La imagen no debe superar los 5 MB.',
            ]
        );

        $this->validarActividadesItinerario(
            $datos['itinerario'] ?? []
        );

        return $datos;
    }

    /**
     * Aplica validaciones que dependen de varios campos.
     */
    private function validarActividadesItinerario(
        array $itinerario
    ): void {
        $errores = [];

        foreach ($itinerario as $diaIndice => $dia) {
            foreach (
                $dia['actividades'] ?? []
                as $actividadIndice => $actividad
            ) {
                $horaInicio = $actividad['hora_inicio'] ?? null;
                $horaFin = $actividad['hora_fin'] ?? null;

                if (
                    $horaInicio &&
                    $horaFin &&
                    $horaFin <= $horaInicio
                ) {
                    $campo =
                        "itinerario.$diaIndice.actividades."
                        . "$actividadIndice.hora_fin";

                    $errores[$campo] =
                        'La hora de finalización debe ser posterior a la hora de inicio.';
                }

                $requiereGestion = filter_var(
                    $actividad['requiere_gestion'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                if (
                    $requiereGestion &&
                    empty($actividad['tipo_gestion'])
                ) {
                    $campo =
                        "itinerario.$diaIndice.actividades."
                        . "$actividadIndice.tipo_gestion";

                    $errores[$campo] =
                        'Selecciona el tipo de gestión que requiere la actividad.';
                }
            }
        }

        if (!empty($errores)) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Elimina elementos vacíos de una lista dinámica.
     */
    private function limpiarLista(array $elementos): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn ($elemento) =>
                        is_string($elemento)
                            ? trim($elemento)
                            : '',
                    $elementos
                ),
                fn ($elemento) => $elemento !== ''
            )
        );
    }

    /**
     * Normaliza, ordena y limpia el itinerario y sus actividades.
     */
    private function limpiarItinerario(array $itinerario): array
    {
        return collect($itinerario)
            ->filter(
                fn ($dia) =>
                    !empty($dia['dia']) &&
                    !empty($dia['titulo']) &&
                    !empty($dia['descripcion'])
            )
            ->map(function ($dia) {
                $actividades = collect(
                    $dia['actividades'] ?? []
                )
                    ->filter(
                        fn ($actividad) =>
                            filled($actividad['nombre'] ?? null)
                    )
                    ->map(function ($actividad) {
                        $requiereGestion = filter_var(
                            $actividad['requiere_gestion'] ?? false,
                            FILTER_VALIDATE_BOOLEAN
                        );

                        $uuid = filled($actividad['uuid'] ?? null)
                            ? (string) $actividad['uuid']
                            : (string) Str::uuid();

                        return [
                            'uuid' => $uuid,

                            'nombre' => trim(
                                (string) $actividad['nombre']
                            ),

                            'descripcion' => filled(
                                $actividad['descripcion'] ?? null
                            )
                                ? trim(
                                    (string) $actividad['descripcion']
                                )
                                : null,

                            'hora_inicio' => filled(
                                $actividad['hora_inicio'] ?? null
                            )
                                ? $actividad['hora_inicio']
                                : null,

                            'hora_fin' => filled(
                                $actividad['hora_fin'] ?? null
                            )
                                ? $actividad['hora_fin']
                                : null,

                            'ubicacion' => filled(
                                $actividad['ubicacion'] ?? null
                            )
                                ? trim(
                                    (string) $actividad['ubicacion']
                                )
                                : null,

                            'requiere_gestion' =>
                                $requiereGestion,

                            'tipo_gestion' => $requiereGestion
                                ? (
                                    $actividad['tipo_gestion']
                                    ?? 'otro'
                                )
                                : null,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'dia' => (int) $dia['dia'],

                    'titulo' => trim(
                        (string) $dia['titulo']
                    ),

                    'descripcion' => trim(
                        (string) $dia['descripcion']
                    ),

                    'actividades' => $actividades,
                ];
            })
            ->sortBy('dia')
            ->values()
            ->all();
    }

    /**
     * Genera una dirección única para la página pública del paquete.
     */
    private function generarSlugUnico(
        string $nombre,
        ?int $destinoId = null
    ): string {
        $slugBase = Str::slug($nombre);

        if ($slugBase === '') {
            $slugBase = 'paquete';
        }

        $slug = $slugBase;
        $numero = 2;

        while (
            Destino::where('slug', $slug)
                ->when(
                    $destinoId,
                    fn ($consulta) =>
                        $consulta->where(
                            'id',
                            '!=',
                            $destinoId
                        )
                )
                ->exists()
        ) {
            $slug = $slugBase . '-' . $numero;
            $numero++;
        }

        return $slug;
    }
}