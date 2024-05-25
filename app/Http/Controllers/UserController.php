<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(?string $id = null): JsonResponse
    {
        try {
            if ($id === null) {
                $id = auth()->user()->id;
            }
            $user = User::find($id);
            $miId = auth()->user()->id;
            $user->isMe = $miId == $id;
            // Comprobar si el usuario autenticado sigue al usuario solicitado
            $user->isFollowing = auth()->user()->following->contains($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'User found',
            'data' => $user
        ]);
    }
}
