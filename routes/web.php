<?php

use App\Http\Controllers\Admin\IntegracoesController;
use App\Http\Controllers\Admin\PainelController;
use App\Http\Controllers\Admin\PapelController;
use App\Http\Controllers\Admin\UnidadeController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\App\AreaController;
use App\Http\Controllers\App\CheckinController;
use App\Http\Controllers\App\InicioController;
use App\Http\Controllers\App\PlaceDetalheController;
use App\Http\Controllers\App\ProspectoController;
use App\Http\Controllers\App\RotaController;
use App\Http\Controllers\App\RotaPageController;
use App\Http\Controllers\App\SetupController;
use App\Http\Controllers\App\TerritorioController;
use App\Services\RedirecionamentoAuthService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $destino = app(RedirecionamentoAuthService::class)->destinoAposLogin(auth()->user());

        return $destino
            ? redirect($destino)
            : redirect()->route('login');
    }

    return redirect()->route('login');
});

Route::middleware(['auth', 'permission:admin.acessar'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', PainelController::class)->name('painel');
        Route::get('/integracoes', IntegracoesController::class)->name('integracoes');

        Route::middleware('permission:unidades.ver')->group(function () {
            Route::get('/unidades', [UnidadeController::class, 'index'])->name('unidades.index');
            Route::get('/unidades/criar', [UnidadeController::class, 'create'])
                ->middleware('permission:unidades.criar')
                ->name('unidades.create');
            Route::post('/unidades', [UnidadeController::class, 'store'])
                ->middleware('permission:unidades.criar')
                ->name('unidades.store');
            Route::post('/unidades/estimar-ceps', [UnidadeController::class, 'estimarCeps'])
                ->middleware('permission:unidades.criar|unidades.editar')
                ->name('unidades.estimar-ceps');
            Route::get('/unidades/{unidade}/editar', [UnidadeController::class, 'edit'])
                ->middleware('permission:unidades.editar')
                ->name('unidades.edit');
            Route::put('/unidades/{unidade}', [UnidadeController::class, 'update'])
                ->middleware('permission:unidades.editar')
                ->name('unidades.update');
        });

        Route::middleware('permission:usuarios.ver')->group(function () {
            Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::get('/usuarios/criar', [UsuarioController::class, 'create'])
                ->middleware('permission:usuarios.criar')
                ->name('usuarios.create');
            Route::post('/usuarios', [UsuarioController::class, 'store'])
                ->middleware('permission:usuarios.criar')
                ->name('usuarios.store');
            Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])
                ->middleware('permission:usuarios.editar')
                ->name('usuarios.edit');
            Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
                ->middleware('permission:usuarios.editar')
                ->name('usuarios.update');
        });

        Route::middleware('permission:papeis.gerenciar')->group(function () {
            Route::get('/papeis', [PapelController::class, 'index'])->name('papeis.index');
            Route::get('/papeis/criar', [PapelController::class, 'create'])->name('papeis.create');
            Route::post('/papeis', [PapelController::class, 'store'])->name('papeis.store');
            Route::get('/papeis/{papel}/editar', [PapelController::class, 'edit'])->name('papeis.edit');
            Route::put('/papeis/{papel}', [PapelController::class, 'update'])->name('papeis.update');
        });
    });

Route::middleware(['auth', 'permission:app.acessar'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::get('/', InicioController::class)->name('inicio');
        Route::get('/setup', SetupController::class)->name('setup');
        Route::get('/area', AreaController::class)->name('area');
        Route::get('/rota', RotaPageController::class)->name('rota');
        Route::get('/checkin', CheckinController::class)->name('checkin');

        Route::post('/area/prospectar', [AreaController::class, 'prospectar'])
            ->middleware('permission:prospectos.buscar')
            ->name('area.prospectar');

        Route::post('/places/detalhe', PlaceDetalheController::class)
            ->middleware('permission:prospectos.ver')
            ->name('places.detalhe');

        Route::post('/territorio/verificar', [TerritorioController::class, 'verificar'])
            ->middleware('permission:territorio.verificar')
            ->name('territorio.verificar');

        Route::post('/prospectos/buscar', [ProspectoController::class, 'buscar'])
            ->middleware('permission:prospectos.buscar')
            ->name('prospectos.buscar');

        Route::post('/rota/gerar', [RotaController::class, 'gerar'])
            ->middleware('permission:prospectos.ver')
            ->name('rota.gerar');

        Route::post('/visitas', [CheckinController::class, 'store'])
            ->middleware('permission:visitas.criar')
            ->name('visitas.store');
    });

require __DIR__.'/auth.php';
