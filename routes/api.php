<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CampoApiController;
use App\Http\Controllers\Api\VendedorApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthApiController::class, 'login']);

Route::middleware(['auth:sanctum', 'permission:app.acessar'])->group(function () {
    Route::get('/auth/me', [AuthApiController::class, 'me']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::get('/vendedor/resumo', [VendedorApiController::class, 'resumo']);

    Route::get('/vendedor/setup', [CampoApiController::class, 'obterSetup']);
    Route::post('/vendedor/setup', [CampoApiController::class, 'salvarSetup']);

    Route::middleware('setup.api')->group(function () {
        Route::get('/vendedor/area/municipios', [CampoApiController::class, 'municipios']);
        Route::get('/vendedor/area/bairros', [CampoApiController::class, 'bairros']);

        Route::post('/vendedor/area/prospectar', [CampoApiController::class, 'prospectar'])
            ->middleware('permission:prospectos.buscar');

        Route::post('/vendedor/territorio/verificar', [CampoApiController::class, 'verificarTerritorio'])
            ->middleware('permission:territorio.verificar');

        Route::post('/vendedor/prospectos/buscar', [CampoApiController::class, 'buscarProspecto'])
            ->middleware('permission:prospectos.buscar');

        Route::post('/vendedor/rota/gerar', [CampoApiController::class, 'gerarRota'])
            ->middleware('permission:prospectos.ver');

        Route::get('/vendedor/rota/hoje', [CampoApiController::class, 'rotaHoje']);
        Route::delete('/vendedor/rota/hoje', [CampoApiController::class, 'cancelarRotaHoje']);

        Route::post('/vendedor/visitas', [CampoApiController::class, 'salvarVisita'])
            ->middleware('permission:visitas.criar');

        Route::post('/vendedor/localizacao', [CampoApiController::class, 'localizacao']);

        Route::post('/vendedor/cercas', [CampoApiController::class, 'cerca'])
            ->middleware('permission:prospectos.buscar');

        Route::post('/vendedor/receita/consultar', [CampoApiController::class, 'receita'])
            ->middleware('permission:prospectos.buscar');

        Route::post('/vendedor/places/detalhe', [CampoApiController::class, 'placeDetalhe'])
            ->middleware('permission:prospectos.ver');
    });
});
