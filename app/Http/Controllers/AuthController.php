<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);
        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->assignRole("admin");
        $user->save();

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente',
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;
            return response()->json([
                'success' => true,
                'message' => 'Usuario logueado correctamente',
                'token' => $token
            ]);
        }
        return response()->json([
            "success" => false,
            "message" => "Error de credenciales",
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();
        $request->user()->tokens()->delete();
        return response()->json([
            "success" => true,
            "message" => "Cierre de sesión correcto"
        ], 401);
    }

    public function checkToken(Request $request): JsonResponse
    {
        return response()->json([
            "success" => true,
            "message" => "Token válido"
        ]);
    }
}
