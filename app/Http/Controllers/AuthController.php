<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var array{user: User, token: string} $result */
        $result = DB::transaction(function () use ($validated): array {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'bio' => $validated['bio'] ?? null,
                'avatar_path' => $validated['avatar_path'] ?? null,
            ]);

            $role = Role::findOrCreate('user', 'web');
            $user->assignRole($role);

            $token = $user->createToken('auth_token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });

        return (new UserResource($result['user']))
            ->additional([
                'message' => 'Usuario registrado correctamente.',
                'token' => $result['token'],
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = (string) $request->validated('login');
        $password = (string) $request->validated('password');

        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->loadCount(['libraryEntries', 'reviews', 'followers', 'following']);
        $token = $user->createToken('auth_token')->plainTextToken;

        return (new UserResource($user))
            ->additional([
                'message' => 'Inicio de sesión exitoso.',
                'token' => $token,
            ])
            ->response();
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadCount(['libraryEntries', 'reviews', 'followers', 'following']);

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
