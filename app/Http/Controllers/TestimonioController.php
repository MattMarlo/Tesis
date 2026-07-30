<?php

namespace App\Http\Controllers;

use App\Models\Testimonio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonioController extends Controller
{
    public function index()
    {
        $testimonios = Testimonio::orderByDesc('destacado')
            ->orderBy('orden')
            ->orderByDesc('created_at')
            ->get();

        return view(
            'modules.testimonios.index',
            compact('testimonios')
        );
    }

    public function create()
    {
        $testimonio = new Testimonio();

        return view(
            'modules.testimonios.create',
            compact('testimonio')
        );
    }

    public function store(Request $request)
    {
        $datos = $this->validarDatos($request);

        if ($request->hasFile('foto')) {
            $datos['foto'] = $request
                ->file('foto')
                ->store('testimonios', 'public');
        }

        $datos['destacado'] = $request->boolean('destacado');

        Testimonio::create($datos);

        return redirect()
            ->route('testimonios.index')
            ->with(
                'success',
                'El testimonio se registró correctamente.'
            );
    }

    public function edit(Testimonio $testimonio)
    {
        return view(
            'modules.testimonios.edit',
            compact('testimonio')
        );
    }

    public function update(
        Request $request,
        Testimonio $testimonio
    ) {
        $datos = $this->validarDatos($request);

        $datos['destacado'] = $request->boolean('destacado');

        if ($request->hasFile('foto')) {
            if (
                $testimonio->foto &&
                Storage::disk('public')->exists($testimonio->foto)
            ) {
                Storage::disk('public')->delete($testimonio->foto);
            }

            $datos['foto'] = $request
                ->file('foto')
                ->store('testimonios', 'public');
        }

        $testimonio->update($datos);

        return redirect()
            ->route('testimonios.index')
            ->with(
                'success',
                'El testimonio se actualizó correctamente.'
            );
    }

    public function destroy(Testimonio $testimonio)
    {
        if (
            $testimonio->foto &&
            Storage::disk('public')->exists($testimonio->foto)
        ) {
            Storage::disk('public')->delete($testimonio->foto);
        }

        $testimonio->delete();

        return redirect()
            ->route('testimonios.index')
            ->with(
                'success',
                'El testimonio se eliminó correctamente.'
            );
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'destino' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'comentario' => [
                    'required',
                    'string',
                    'min:10',
                    'max:1000',
                ],
                'calificacion' => [
                    'required',
                    'integer',
                    'between:1,5',
                ],
                'foto' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:4096',
                ],
                'estado' => [
                    'required',
                    'in:pendiente,publicado,oculto',
                ],
                'orden' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:9999',
                ],
                'destacado' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'nombre.required' =>
                    'Ingresa el nombre del cliente.',
                'nombre.max' =>
                    'El nombre no puede superar los 100 caracteres.',

                'destino.max' =>
                    'El destino no puede superar los 150 caracteres.',

                'comentario.required' =>
                    'Ingresa el comentario del cliente.',
                'comentario.min' =>
                    'El comentario debe tener al menos 10 caracteres.',
                'comentario.max' =>
                    'El comentario no puede superar los 1000 caracteres.',

                'calificacion.required' =>
                    'Selecciona una calificación.',
                'calificacion.integer' =>
                    'La calificación seleccionada no es válida.',
                'calificacion.between' =>
                    'La calificación debe estar entre una y cinco estrellas.',

                'foto.image' =>
                    'El archivo seleccionado debe ser una imagen.',
                'foto.mimes' =>
                    'La fotografía debe estar en formato JPG, PNG o WEBP.',
                'foto.max' =>
                    'La fotografía no puede superar los 4 MB.',

                'estado.required' =>
                    'Selecciona el estado del testimonio.',
                'estado.in' =>
                    'El estado seleccionado no es válido.',

                'orden.integer' =>
                    'El orden debe ser un número entero.',
                'orden.min' =>
                    'El orden no puede ser negativo.',
                'orden.max' =>
                    'El orden ingresado es demasiado alto.',
            ]
        );
    }
}