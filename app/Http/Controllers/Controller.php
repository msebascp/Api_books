<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Usuario que realiza la petición, si envía un token Sanctum válido.
     *
     * Funciona también en rutas públicas (sin middleware auth:sanctum), donde
     * $request->user() consultaría el guard por defecto ('web') y devolvería null.
     */
    protected function viewer(Request $request): ?User
    {
        $user = $request->user('sanctum');

        return $user instanceof User ? $user : null;
    }
}
