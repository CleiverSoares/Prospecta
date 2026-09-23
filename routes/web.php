<?php

use App\Http\Controllers\Admin\DocumentacaoController;
use App\Http\Controllers\Admin\IntegracoesController;
use App\Http\Controllers\Admin\AgendaDiaController;
use App\Http\Controllers\Admin\LocalizacaoAoVivoController;
use App\Http\Controllers\Admin\LocalizacaoTrajetoController;
use App\Http\Controllers\Admin\PainelController;
use App\Http\Controllers\Admin\PapelController;
use App\Http\Controllers\Admin\UnidadeController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\VisitaController;
use App\Http\Controllers\App\AreaController;
use App\Http\Controllers\App\CercaController;
use App\Http\Controllers\App\CheckinController;
use App\Http\Controllers\App\InicioController;
use App\Http\Controllers\App\LocalizacaoController;
use App\Http\Controllers\App\PlaceDetalheController;
use App\Http\Controllers\App\ProspectoController;
use App\Http\Controllers\App\ReceitaController;
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
        Route::get('/agenda', AgendaDiaController::class)->name('agenda');
        Route::get('/integracoes', IntegracoesController::class)->name('integracoes');
        Route::get('/documentacao', DocumentacaoController::class)->name('documentacao');
        Route::get('/localizacoes/ao-vivo', LocalizacaoAoVivoController::class)->name('localizacoes.ao-vivo');
        Route::get('/localizacoes/{usuario}/trajeto', LocalizacaoTrajetoController::class)
            ->middleware('permission:usuarios.ver')
            ->name('localizacoes.trajeto');

        Route::middleware('permission:visitas.ver')->group(function () {
            Route::get('/visitas', [VisitaController::class, 'index'])->name('visitas.index');
            Route::get('/visitas/{visita}', [VisitaController::class, 'show'])->name('visitas.show');
            Route::get('/visitas/{visita}/foto', [VisitaController::class, 'foto'])->name('visitas.foto');
            Route::get('/visitas/{visita}/audio', [VisitaController::class, 'audio'])->name('visitas.audio');
        });

        Route::middleware('permission:unidades.ver')->group(function () {
            Route::get('/unidades', [UnidadeController::class, 'index'])->name('unidades.index');
            Route::get('/unidades/criar', [UnidadeController::class, 'create'])
                ->middleware('permission:unidades.criar')
                ->name('unidades.create');
            Route::post('/unidades', [UnidadeController::class, 'store'])
                ->middleware('permission:unidades.criar')
                ->name('unidades.store');
            Route::post('/unidades/rascunho-poligono', [UnidadeController::class, 'rascunhoPoligono'])
                ->middleware('permission:unidades.criar')
                ->name('unidades.rascunho-poligono');
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
            Route::get('/usuarios/{usuario}', function (\App\Models\User $usuario) {
                return redirect()->route('admin.usuarios.edit', $usuario);
            })->name('usuarios.show');
            Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
                ->middleware('permission:usuarios.editar')
                ->name('usuarios.update');
            Route::post('/usuarios/{usuario}/desativar', [UsuarioController::class, 'desativar'])
                ->middleware('permission:usuarios.editar')
                ->name('usuarios.desativar');
            Route::post('/usuarios/{usuario}/reativar', [UsuarioController::class, 'reativar'])
                ->middleware('permission:usuarios.editar')
                ->name('usuarios.reativar');
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
        Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

        Route::middleware('setup.diario')->group(function () {
            Route::get('/area', AreaController::class)->name('area');
            Route::get('/area/municipios', [AreaController::class, 'municipios'])
                ->name('area.municipios');
            Route::get('/area/bairros', [AreaController::class, 'bairros'])
                ->name('area.bairros');
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

            Route::post('/cercas', [CercaController::class, 'store'])
                ->middleware('permission:prospectos.buscar')
                ->name('cercas.store');

            Route::post('/localizacao', [LocalizacaoController::class, 'store'])
                ->name('localizacao.store');

            Route::post('/receita/consultar', [ReceitaController::class, 'consultar'])
                ->middleware('permission:prospectos.buscar')
                ->name('receita.consultar');

            Route::post('/visitas', [CheckinController::class, 'store'])
                ->middleware('permission:visitas.criar')
                ->name('visitas.store');
        });
    });

require __DIR__.'/auth.php';
