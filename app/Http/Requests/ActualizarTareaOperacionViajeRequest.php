<?php

namespace App\Http\Requests;

use App\Models\TareaOperacionViaje;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActualizarTareaOperacionViajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * La ruta estará dentro del middleware auth.
         */
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('observaciones')) {
            $observaciones = trim(
                (string) $this->input('observaciones')
            );

            $this->merge([
                'observaciones' =>
                    $observaciones !== ''
                        ? $observaciones
                        : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',

                Rule::in([
                    TareaOperacionViaje::ESTADO_PENDIENTE,
                    TareaOperacionViaje::ESTADO_EN_PROCESO,
                    TareaOperacionViaje::ESTADO_COMPLETADA,
                    TareaOperacionViaje::ESTADO_OMITIDA,
                ]),
            ],

            'observaciones' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /*
                 * Una tarea omitida debe tener una justificación
                 * para conservar la trazabilidad operativa.
                 */
                if (
                    $this->input('estado') ===
                        TareaOperacionViaje::ESTADO_OMITIDA &&
                    blank($this->input('observaciones'))
                ) {
                    $validator->errors()->add(
                        'observaciones',
                        'Debes indicar el motivo por el que se omite la tarea.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' =>
                'Selecciona el estado de la tarea.',

            'estado.in' =>
                'El estado seleccionado no es válido.',

            'observaciones.string' =>
                'Las observaciones deben ser un texto válido.',

            'observaciones.max' =>
                'Las observaciones no pueden superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'estado' =>
                'estado',

            'observaciones' =>
                'observaciones',
        ];
    }
}