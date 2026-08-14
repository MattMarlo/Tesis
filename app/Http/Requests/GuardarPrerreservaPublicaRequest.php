<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarPrerreservaPublicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre_completo' => trim(
                (string) $this->input('nombre_completo')
            ),
            'cedula' => preg_replace(
                '/\D+/',
                '',
                (string) $this->input('cedula')
            ),
            'correo' => mb_strtolower(
                trim((string) $this->input('correo'))
            ),
            'telefono' => preg_replace(
                '/[\s\-()]+/',
                '',
                trim((string) $this->input('telefono'))
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_reserva' => [
                'required',
                Rule::in(['individual', 'grupal']),
            ],
            'cantidad_personas' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'nombre_completo' => [
                'required',
                'string',
                'min:5',
                'max:150',
            ],
            'cedula' => [
                'required',
                'digits:10',
            ],
            'correo' => [
                'required',
                'email:rfc',
                'max:150',
            ],
            'telefono' => [
                'required',
                'regex:/^(?:\+593|0)9\d{8}$/',
            ],
            'acepta_condiciones' => [
                'accepted',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tipo = $this->input('tipo_reserva');
                $cantidad = (int) $this->input(
                    'cantidad_personas',
                    0
                );

                if (
                    $tipo === 'individual'
                    && $cantidad !== 1
                ) {
                    $validator->errors()->add(
                        'cantidad_personas',
                        'La prerreserva individual debe ser para una persona.'
                    );
                }

                if (
                    $tipo === 'grupal'
                    && $cantidad < 2
                ) {
                    $validator->errors()->add(
                        'cantidad_personas',
                        'La prerreserva grupal requiere al menos dos personas.'
                    );
                }

                $cedula = (string) $this->input('cedula');

                if (
                    preg_match('/^\d{10}$/', $cedula)
                    && ! $this->cedulaEcuatorianaValida($cedula)
                ) {
                    $validator->errors()->add(
                        'cedula',
                        'La cédula ecuatoriana no es válida.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_reserva.required' => 'Indica si la prerreserva es individual o grupal.',
            'tipo_reserva.in' => 'El tipo de prerreserva seleccionado no es válido.',
            'cantidad_personas.required' => 'Indica cuántas personas viajarán.',
            'cantidad_personas.integer' => 'La cantidad de personas debe ser un número entero.',
            'cantidad_personas.min' => 'Debe viajar al menos una persona.',
            'cantidad_personas.max' => 'La cantidad no puede superar 100 personas.',
            'nombre_completo.required' => 'Ingresa tu nombre completo.',
            'nombre_completo.min' => 'Ingresa al menos un nombre y un apellido.',
            'nombre_completo.max' => 'El nombre no puede superar 150 caracteres.',
            'cedula.required' => 'Ingresa tu número de cédula.',
            'cedula.digits' => 'La cédula debe tener exactamente 10 dígitos.',
            'correo.required' => 'Ingresa tu correo electrónico.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar 150 caracteres.',
            'telefono.required' => 'Ingresa tu número de celular.',
            'telefono.regex' => 'Ingresa un celular ecuatoriano válido, por ejemplo 0991234567.',
            'acepta_condiciones.accepted' => 'Debes aceptar las condiciones y la política de privacidad.',
        ];
    }

    private function cedulaEcuatorianaValida(
        string $cedula
    ): bool {
        if (
            (int) substr($cedula, 0, 2) < 1
            || (int) substr($cedula, 0, 2) > 24
            || (int) $cedula[2] > 5
        ) {
            return false;
        }

        $suma = 0;

        for ($indice = 0; $indice < 9; $indice++) {
            $valor = (int) $cedula[$indice]
                * ($indice % 2 === 0 ? 2 : 1);

            $suma += $valor > 9
                ? $valor - 9
                : $valor;
        }

        return (
            (10 - ($suma % 10)) % 10
        ) === (int) $cedula[9];
    }
}
