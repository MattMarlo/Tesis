<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403, 'No tienes permisos para acceder a este módulo.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isAgente() && in_array($permission, ['usuarios.ver', 'usuarios.crear'], true)) {
            abort(403, 'No tienes permisos para acceder a este módulo.');
        }

        return $next($request);
    }
}
