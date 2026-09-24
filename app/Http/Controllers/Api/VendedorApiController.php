<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendedorApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendedorApiController extends Controller
{
    public function __construct(
        private readonly VendedorApiService $vendedorApiService,
    ) {}

    public function resumo(Request $request): JsonResponse
    {
        return response()->json(
            $this->vendedorApiService->resumo($request->user()),
        );
    }
}
