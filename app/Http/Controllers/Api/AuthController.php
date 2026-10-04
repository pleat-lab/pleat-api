<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // El rol no se toma del request: todo registro público es customer.
        $user = User::create($request->validated());

        // additional() agrega el token junto a "data": { "data": {...}, "token": "..." }.
        return (new UserResource($user))
            ->additional(['token' => $user->createToken('api')->plainTextToken])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $user = User::where('email', $request->email)->first();

        // Mismo mensaje si falla el correo o la contraseña, para no revelar qué correos existen.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales son incorrectas.',
            ]);
        }

        return (new UserResource($user))
            ->additional(['token' => $user->createToken('api')->plainTextToken]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
