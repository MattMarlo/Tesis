<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $datos = $request->validate(
            [
                'email' => [
                    'required',
                    'email',
                    'max:100',
                ],
                'password' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ],
            [
                'email.required' =>
                    'Ingresa tu correo electrónico.',
                'email.email' =>
                    'Escribe un correo electrónico válido.',
                'password.required' =>
                    'Ingresa tu contraseña.',
            ]
        );

        $correo = mb_strtolower(trim($datos['email']));

        $usuario = User::where('email', $correo)->first();

        if (
            !$usuario ||
            !Hash::check($datos['password'], $usuario->password)
        ) {
            return back()
                ->withErrors([
                    'email' =>
                        'El correo o la contraseña no son correctos.',
                ])
                ->onlyInput('email');
        }

        if (!$usuario->estaActivo()) {
            return back()
                ->withErrors([
                    'email' =>
                        'Tu cuenta está inactiva. Comunícate con la administradora.',
                ])
                ->onlyInput('email');
        }

        Auth::login($usuario);

        $request->session()->regenerate();

        return redirect()
            ->route('main')
            ->with(
                'success',
                'Bienvenido al sistema, ' . $usuario->nombres . '.'
            );
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Te hemos enviado un enlace para recuperar tu contraseña. Revisa tu correo electrónico.');
        }

        return back()->withErrors([
            'email' => __($status),
        ])->onlyInput('email');
    }

    public function showResetPassword(string $token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Tu contraseña ha sido restablecida correctamente.');
        }

        return back()->withErrors([
            'email' => __($status),
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $datos = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'required|string|max:20',
            'documento' => 'required|string|max:30|unique:users,documento',
            'rol' => 'required|in:admin,agente',
            'password' => 'required|min:8|confirmed',
        ]);

        $datos['password'] = Hash::make($datos['password']);

        $user = User::create($datos);

        Auth::login($user);

        return redirect('/login')->with('success', 'Cuenta creada exitosamente. Bienvenido!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
