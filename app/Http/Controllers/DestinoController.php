<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DestinoController extends Controller
{
    /**
     * Muestra todos los paquetes turísticos.
     */
    public function index()
    {
        $titulo = 'Paquetes turísticos';

        $destinos = Destino::orderBy('created_at', 'desc')->get();

        return view(
            'modules.destinos.index',
            compact('titulo', 'destinos')
        );
    }

    /**
     * Muestra el formulario para crear un paquete.
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

        try {
            $datos['imagen'] = $request
                ->file('imagen')
                ->store('destinos', 'public');

            Destino::create($datos);

            return to_route('destinos')->with(
                'success',
                'El paquete turístico se registró correctamente.'
            );
        } catch (Exception $e) {
            report($e);

            if (!empty($datos['imagen'])) {
                Storage::disk('public')->delete($datos['imagen']);
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
     * Elimina un paquete que no tenga reservas.
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
     * Valida los datos recibidos desde los formularios.
     */
    private function validarDatos(
            Request $request,
            bool $esCreacion
        ): array {
            $request->merge([
                'incluye' => $this->limpiarLista(
                    $request->input('incluye', [])
                ),

                'no_incluye' => $this->limpiarLista(
                    $request->input('no_incluye', [])
                ),
            ]);
            return $request->validate(
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
                ],

                'ciudad_destino' => [
                    'required',
                    'string',
                    'max:100',
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
                ],

                'fecha_salida' => [
                    'required',
                    'date',
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

                'aerolinea' => [
                    'nullable',
                    'string',
                    'max:120',
                ],

                'hotel' => [
                    'nullable',
                    'string',
                    'max:150',
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

                'itinerario' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'itinerario.*.dia' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:365',
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

                'ciudad_destino.required' =>
                    'La ciudad de destino es obligatoria.',

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

                'fecha_salida.required' =>
                    'La fecha de salida es obligatoria.',

                'fecha_regreso.required' =>
                    'La fecha de regreso es obligatoria.',

                'fecha_regreso.after_or_equal' =>
                    'La fecha de regreso no puede ser anterior a la salida.',

                'precio.required' =>
                    'El precio del paquete es obligatorio.',

                'precio_promocional.lt' =>
                    'El precio promocional debe ser menor al precio normal.',

                'dias.required' =>
                    'La duración en días es obligatoria.',

                'noches.required' =>
                    'La duración en noches es obligatoria.',

                'noches.lte' =>
                    'La cantidad de noches no puede superar los días.',

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

                'estado_publicacion.required' =>
                    'Selecciona el estado de publicación.',

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
    }

    /**
     * Elimina elementos vacíos de una lista.
     */
    private function limpiarLista(array $elementos): array
    {
        return array_values(
            array_filter(
                $elementos,
                fn ($elemento) =>
                    is_string($elemento) &&
                    trim($elemento) !== ''
            )
        );
    }

    /**
     * Organiza los días válidos del itinerario.
     */
    private function limpiarItinerario(array $itinerario): array
    {
        return array_values(
            array_filter(
                $itinerario,
                fn ($dia) =>
                    !empty($dia['dia']) &&
                    !empty($dia['titulo']) &&
                    !empty($dia['descripcion'])
            )
        );
    }

    /**
     * Genera una dirección única para la página del paquete.
     */
    private function generarSlugUnico(
        string $nombre,
        ?int $destinoId = null
    ): string {
        $slugBase = Str::slug($nombre);
        $slug = $slugBase;
        $numero = 2;

        while (
            Destino::where('slug', $slug)
                ->when(
                    $destinoId,
                    fn ($consulta) =>
                        $consulta->where('id', '!=', $destinoId)
                )
                ->exists()
        ) {
            $slug = $slugBase . '-' . $numero;
            $numero++;
        }

        return $slug;
    }

    public function detalle(string $slug)
    {
        $destino = Destino::where('slug', $slug)
            ->where('estado_publicacion', 'publicado')
            ->firstOrFail();

        return view('paquetes.detalle', compact('destino'));
    }
}