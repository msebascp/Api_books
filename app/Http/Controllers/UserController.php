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
            $me = auth()->user();
            $user->isMe = $me->id == $id;
            // Comprobar si el usuario autenticado sigue al usuario solicitado
            $user->isFollowing = $me->following->contains($id);

            return response()->json([
                'success' => true,
                'message' => 'User found',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(string $username): JsonResponse
    {
        try {
            $users = User::where('username', 'like', "%$username%")->get();

            return response()->json([
                'success' => true,
                'message' => 'Users found',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
