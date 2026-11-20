<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  Roles permitidos (por defecto 'admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        // 1. Si no está logueado o el token es inválido -> 401 Unauthorized
        abort_if($user === null, Response::HTTP_UNAUTHORIZED, 'No autenticado.');
        // 2. Si no se especifican roles en la ruta, por defecto exige 'admin'
        $allowedRoles = empty($roles) ? ['admin'] : $roles;
        // 3. Si no tiene el rol necesario -> 403 Forbidden
        abort_if(
            ! $user->hasAnyRole($allowedRoles),
            Response::HTTP_FORBIDDEN,
            'No tienes permisos para acceder a este recurso.'
        );

        return $next($request);
    }
}
