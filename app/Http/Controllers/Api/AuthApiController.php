<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginApiRequest;
use App\Services\AuthApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApiController extends Controller
{
    public function __construct(
        private readonly AuthApiService $authApiService,
    ) {}

    public function login(LoginApiRequest $request): JsonResponse
    {
        $resultado = $this->authApiService->login(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name')->toString(),
        );

        return response()->json($resultado);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => $this->authApiService->serializarUsuario($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authApiService->logout($request->user());

        return response()->json(['ok' => true]);
    }
}
