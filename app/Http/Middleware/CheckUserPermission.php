<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permiso
    ): Response {
        $usuario = $request->user();

        if (!$usuario instanceof User) {
            abort(
                403,
                'Debes iniciar sesión para acceder a esta opción.'
            );
        }

        if (!$usuario->estaActivo()) {
            abort(
                403,
                'Tu cuenta se encuentra inactiva. Comunícate con la administradora.'
            );
        }

        if (!$usuario->hasPermission($permiso)) {
            abort(
                403,
                'No tienes permiso para acceder a esta opción.'
            );
        }

        return $next($request);
    }
}