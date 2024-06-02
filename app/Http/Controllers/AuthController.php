<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPassword;
use App\Mail\WelcomeUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->username = $request->username;
            $user->assignRole("user");
            $user->save();

            $token = $user->createToken('authToken')->plainTextToken;
            Mail::to($user->email)->send(new WelcomeUser());
            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);
        try {
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
                "message" => "Error credentials",
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error to login user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();
            return response()->json([
                "success" => true,
                "message" => "Logout successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkToken(): JsonResponse
    {
        return response()->json([
            "success" => true,
            "message" => "Token válido"
        ]);
    }

    public function forgotPassword(string $email): JsonResponse
    {
        try {
            if (User::where('email', $email)->exists()) {
                $user = User::where('email', $email)->first();
                $password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
                $user->password = Hash::make($password);
                $user->save();
                Mail::to($email)->send(new ForgotPassword($password, $user));
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
