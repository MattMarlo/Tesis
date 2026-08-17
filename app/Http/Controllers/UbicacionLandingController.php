<?php

namespace App\Http\Controllers;

use App\Models\UbicacionLanding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UbicacionLandingController extends Controller
{
    public function edit(Request $request): View
    {
        $this->autorizar($request);

        $ubicacion = UbicacionLanding::actual();

        return view(
            'modules.ubicacion.edit',
            compact('ubicacion')
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $this->autorizar($request);

        $datos = $request->validate(
            [
                'localidad' => ['required', 'string', 'max:100'],
                'direccion' => ['required', 'string', 'max:255'],
                'consulta_mapa' => ['required', 'string', 'max:255'],
                'enlace_mapa' => [
                    'required',
                    'url',
                    'max:2048',
                    'starts_with:https://',
                ],
                'latitud' => [
                    'nullable',
                    'numeric',
                    'between:-90,90',
                    'required_with:longitud',
                ],
                'longitud' => [
                    'nullable',
                    'numeric',
                    'between:-180,180',
                    'required_with:latitud',
                ],
            ],
            [
                'localidad.required' => 'Ingresa la localidad de la agencia.',
                'localidad.max' => 'La localidad no puede superar los 100 caracteres.',
                'direccion.required' => 'Ingresa la dirección que se mostrará en la página.',
                'direccion.max' => 'La dirección no puede superar los 255 caracteres.',
                'consulta_mapa.required' => 'Ingresa una referencia para localizar la agencia en el mapa.',
                'consulta_mapa.max' => 'La referencia del mapa no puede superar los 255 caracteres.',
                'enlace_mapa.required' => 'Ingresa el enlace de Google Maps.',
                'enlace_mapa.url' => 'Ingresa un enlace válido de Google Maps.',
                'enlace_mapa.max' => 'El enlace de Google Maps es demasiado largo.',
                'enlace_mapa.starts_with' => 'El enlace de Google Maps debe comenzar con https://.',
                'latitud.numeric' => 'La latitud seleccionada no es válida.',
                'latitud.between' => 'La latitud seleccionada está fuera del rango permitido.',
                'latitud.required_with' => 'Selecciona nuevamente el punto en el mapa.',
                'longitud.numeric' => 'La longitud seleccionada no es válida.',
                'longitud.between' => 'La longitud seleccionada está fuera del rango permitido.',
                'longitud.required_with' => 'Selecciona nuevamente el punto en el mapa.',
            ]
        );

        $ubicacion = UbicacionLanding::query()->first()
            ?? new UbicacionLanding;

        $ubicacion->fill($datos);
        $ubicacion->save();

        return redirect()
            ->route('ubicacion.edit')
            ->with(
                'success',
                'La ubicación de la página pública se actualizó correctamente.'
            );
    }

    private function autorizar(Request $request): void
    {
        abort_unless(
            $request->user()?->isAdmin(),
            403,
            'No tienes permiso para administrar la ubicación.'
        );
    }
}
