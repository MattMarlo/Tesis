<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $titulo = 'Usuarios';

        $usuarios = User::orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        return view(
            'modules.usuarios.index',
            compact('titulo', 'usuarios')
        );
    }

    public function create()
    {
        $titulo = 'Registrar usuario';
        $usuario = new User();

        return view(
            'modules.usuarios.create',
            compact('titulo', 'usuario')
        );
    }

    public function store(Request $request)
    {
        $datos = $request->validate(
            $this->reglas(),
            $this->mensajesValidacion()
        );

        try {
            $datos = $this->prepararDatos($datos);

            User::create($datos);

            return redirect()
                ->route('usuarios')
                ->with(
                    'success',
                    'El usuario se registró correctamente.'
                );
        } catch (Throwable $error) {
            Log::error(
                'No se pudo registrar el usuario.',
                ['error' => $error->getMessage()]
            );

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'No fue posible registrar el usuario. Intenta nuevamente.'
                );
        }
    }

    public function edit(string $id)
    {
        $titulo = 'Editar usuario';
        $usuario = User::findOrFail($id);

        return view(
            'modules.usuarios.edit',
            compact('titulo', 'usuario')
        );
    }

    public function update(
        Request $request,
        string $id
    ) {
        $usuario = User::findOrFail($id);

        $datos = $request->validate(
            $this->reglas($usuario),
            $this->mensajesValidacion()
        );

        /*
         * La administradora que está utilizando el sistema
         * no puede quitarse sus propios permisos ni desactivarse.
         */
        if (
            auth()->id() === $usuario->id &&
            (
                $datos['rol'] !== User::ROL_ADMIN ||
                $datos['estado'] !== User::ESTADO_ACTIVO
            )
        ) {
            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'No puedes cambiar tu propio rol ni desactivar tu cuenta.'
                );
        }

        /*
         * El sistema siempre debe conservar al menos un
         * administrador activo.
         */
        $dejaDeSerAdministrador =
            $usuario->isAdmin() &&
            $usuario->estaActivo() &&
            (
                $datos['rol'] !== User::ROL_ADMIN ||
                $datos['estado'] !== User::ESTADO_ACTIVO
            );

        if (
            $dejaDeSerAdministrador &&
            !$this->existeOtroAdministradorActivo($usuario)
        ) {
            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'Debe permanecer al menos un administrador activo.'
                );
        }

        try {
            $datos = $this->prepararDatos(
                $datos,
                $usuario
            );

            $usuario->update($datos);

            return redirect()
                ->route('usuarios')
                ->with(
                    'success',
                    'El usuario se actualizó correctamente.'
                );
        } catch (Throwable $error) {
            Log::error(
                'No se pudo actualizar el usuario.',
                [
                    'usuario_id' => $usuario->id,
                    'error' => $error->getMessage(),
                ]
            );

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'No fue posible actualizar el usuario. Intenta nuevamente.'
                );
        }
    }

    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);

        if (auth()->id() === $usuario->id) {
            return back()->with(
                'error',
                'No puedes eliminar tu propia cuenta.'
            );
        }

        if (
            $usuario->isAdmin() &&
            $usuario->estaActivo() &&
            !$this->existeOtroAdministradorActivo($usuario)
        ) {
            return back()->with(
                'error',
                'No puedes eliminar al último administrador activo.'
            );
        }

        /*
         * Se conserva la trazabilidad de quién registró reservas
         * y pagos. En estos casos la cuenta debe desactivarse.
         */
        if (
            $usuario->reservas()->exists() ||
            $usuario->pagos()->exists()
        ) {
            return back()->with(
                'error',
                'Este usuario tiene reservas o pagos registrados. Puedes desactivar su cuenta, pero no eliminarla.'
            );
        }

        try {
            $usuario->delete();

            return redirect()
                ->route('usuarios')
                ->with(
                    'success',
                    'El usuario se eliminó correctamente.'
                );
        } catch (Throwable $error) {
            Log::error(
                'No se pudo eliminar el usuario.',
                [
                    'usuario_id' => $usuario->id,
                    'error' => $error->getMessage(),
                ]
            );

            return back()->with(
                'error',
                'No fue posible eliminar el usuario.'
            );
        }
    }

    private function reglas(?User $usuario = null): array
    {
        $reglaContrasena = $usuario
            ? [
                'nullable',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ]
            : [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ];

        return [
            'nombres' => [
                'required',
                'string',
                'max:100',
                "regex:/^[\pL\s'-]+$/u",
            ],
            'apellidos' => [
                'required',
                'string',
                'max:100',
                "regex:/^[\pL\s'-]+$/u",
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')
                    ->ignore($usuario?->id),
            ],
            'telefono' => [
                'required',
                'string',
                'min:7',
                'max:20',
                'regex:/^[0-9+\s()-]+$/',
            ],
            'documento' => [
                'required',
                'string',
                'min:5',
                'max:30',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('users', 'documento')
                    ->ignore($usuario?->id),
            ],
            'rol' => [
                'required',
                Rule::in([
                    User::ROL_ADMIN,
                    User::ROL_AGENTE,
                ]),
            ],
            'estado' => [
                'required',
                Rule::in([
                    User::ESTADO_ACTIVO,
                    User::ESTADO_INACTIVO,
                ]),
            ],
            'password' => $reglaContrasena,
        ];
    }

    private function mensajesValidacion(): array
    {
        return [
            'nombres.required' =>
                'Ingresa los nombres.',
            'nombres.regex' =>
                'Los nombres solo pueden contener letras, espacios, apóstrofes o guiones.',
            'nombres.max' =>
                'Los nombres no pueden superar los 100 caracteres.',

            'apellidos.required' =>
                'Ingresa los apellidos.',
            'apellidos.regex' =>
                'Los apellidos solo pueden contener letras, espacios, apóstrofes o guiones.',
            'apellidos.max' =>
                'Los apellidos no pueden superar los 100 caracteres.',

            'email.required' =>
                'Ingresa el correo electrónico.',
            'email.email' =>
                'Escribe un correo electrónico válido.',
            'email.unique' =>
                'Este correo ya está registrado.',

            'telefono.required' =>
                'Ingresa el número de teléfono.',
            'telefono.min' =>
                'El teléfono debe tener al menos 7 caracteres.',
            'telefono.max' =>
                'El teléfono no puede superar los 20 caracteres.',
            'telefono.regex' =>
                'El teléfono contiene caracteres no permitidos.',

            'documento.required' =>
                'Ingresa el número de identificación.',
            'documento.min' =>
                'La identificación debe tener al menos 5 caracteres.',
            'documento.regex' =>
                'La identificación solo puede contener letras, números y guiones.',
            'documento.unique' =>
                'Este número de identificación ya está registrado.',

            'rol.required' =>
                'Selecciona el tipo de usuario.',
            'rol.in' =>
                'El tipo de usuario seleccionado no es válido.',

            'estado.required' =>
                'Selecciona el estado de la cuenta.',
            'estado.in' =>
                'El estado seleccionado no es válido.',

            'password.required' =>
                'Ingresa una contraseña.',
            'password.confirmed' =>
                'Las contraseñas no coinciden.',
        ];
    }

    private function prepararDatos(
        array $datos,
        ?User $usuario = null
    ): array {
        $datos['nombres'] = trim($datos['nombres']);
        $datos['apellidos'] = trim($datos['apellidos']);
        $datos['email'] = mb_strtolower(
            trim($datos['email'])
        );
        $datos['telefono'] = trim($datos['telefono']);
        $datos['documento'] = mb_strtoupper(
            trim($datos['documento'])
        );

        if (
            $usuario &&
            empty($datos['password'])
        ) {
            unset($datos['password']);
        }

        return $datos;
    }

    private function existeOtroAdministradorActivo(
        User $usuario
    ): bool {
        return User::where(
            'rol',
            User::ROL_ADMIN
        )
            ->where(
                'estado',
                User::ESTADO_ACTIVO
            )
            ->where('id', '!=', $usuario->id)
            ->exists();
    }
}