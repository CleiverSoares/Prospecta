<?php

use App\Http\Controllers\Admin\PainelController;
use App\Http\Controllers\Admin\PapelController;
use App\Http\Controllers\App\InicioController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'permission:admin.acessar'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', PainelController::class)->name('painel');

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
    });

require __DIR__.'/auth.php';
