<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (
            $user === null
            || ! Hash::check($validated['password'], $user->password)
        ) {
            return ApiResponse::error(
                code: 'INVALID_CREDENTIALS',
                message: 'Datele de autentificare nu sunt corecte.',
                status: 401,
            );
        }

        $expiresAt = now()->addDays(30);

        $token = $user->createToken(
            $validated['device_name'],
            ['*'],
            $expiresAt
        );

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => (new AuthenticatedUserResource($user))->resolve(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            (new AuthenticatedUserResource(
                $request->user()
            ))->resolve()
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return ApiResponse::success([
            'message' => 'Deconectarea a fost efectuată cu succes.',
        ]);
    }
}
