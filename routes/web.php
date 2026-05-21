<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ReservaIndividualController;
use App\Http\Controllers\ReservaGrupalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PagoController;

Route::get('/', function () {
    return view('layouts.main');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('auth')->group(function() {
    Route::prefix('usuarios')->group(function() {
        Route::get('/', [UserController::class, 'index'])->name('usuarios');
        Route::get('/create', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/store', [UserController::class, 'store'])->name('usuarios.store');
        Route::delete('/destroy/{id}', [UserController::class, 'destroy'])->name('usuarios.destroy');
        Route::get('/edit/{id}', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/update/{id}', [UserController::class, 'update'])->name('usuarios.update');
    });
    Route::prefix('clientes')->group(function() {
        Route::get('/', [ClienteController::class, 'index'])->name('clientes');
        Route::get('/create', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('/store', [ClienteController::class, 'store'])->name('clientes.store');
        Route::delete('/destroy/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::get('/edit/{id}', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('/update/{id}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::get('/buscar-cedula', [ClienteController::class, 'buscarPorDocumento'])->name('clientes.buscarDocumento');
    });
    Route::prefix('destinos')->group(function() {
        Route::get('/', [DestinoController::class, 'index'])->name('destinos');
        Route::get('/create', [DestinoController::class, 'create'])->name('destinos.create');
        Route::post('/store', [DestinoController::class, 'store'])->name('destinos.store');
        Route::delete('/destroy/{id}', [DestinoController::class, 'destroy'])->name('destinos.destroy');
        Route::get('/edit/{id}', [DestinoController::class, 'edit'])->name('destinos.edit');
        Route::put('/update/{id}', [DestinoController::class, 'update'])->name('destinos.update');
    });
    Route::prefix('grupos')->group(function() {
        Route::get('/', [GrupoController::class, 'index'])->name('grupos');
        Route::get('/create', [GrupoController::class, 'create'])->name('grupos.create');
        Route::post('/store', [GrupoController::class, 'store'])->name('grupos.store');
        Route::delete('/destroy/{id}', [GrupoController::class, 'destroy'])->name('grupos.destroy');
        Route::get('/edit/{id}', [GrupoController::class, 'edit'])->name('grupos.edit');
        Route::put('/update/{id}', [GrupoController::class, 'update'])->name('grupos.update');
    });
    Route::prefix('reservas')->group(function () {
        Route::get('/', [ReservaController::class, 'index'])->name('reservas');
        Route::get('/{reserva}/detalle', [ReservaController::class, 'detalleJson'])->name('reservas.detalle');
        Route::post('/{reserva}/integrantes/guardar', [ReservaController::class, 'guardarIntegrantes'])->name('reservas.integrantes.guardar');
        Route::put('/{reserva}', [ReservaController::class, 'update'])->name('reservas.update');
        Route::put('/integrantes/{id}/update-fast', [ReservaController::class, 'updateIntegranteFast'])->name('integrantes.updateFast');
        Route::delete('/{reserva}', [ReservaController::class, 'destroy'])->name('reservas.destroy');
    });

    Route::prefix('pagos')->group(function () {
        Route::get('/', [PagoController::class, 'index'])->name('pagos');
        Route::post('/store', [PagoController::class, 'store'])->name('pagos.store');
        Route::put('/integrante-grupal', [PagoController::class, 'updateIntegrante'])->name('pagos.integrante');
        Route::get('/grupo/{reserva}', [PagoController::class, 'showGrupoDetails'])->name('pagos.grupo');
        Route::get('/reserva/{reservaId}/pagos-lista', [PagoController::class, 'listaPagosReserva'])->name('pagos.lista');
        Route::get('/{pago}/auditoria', [PagoController::class, 'auditoria'])->name('pagos.auditoria');
        Route::put('/{pago}', [PagoController::class, 'update'])->name('pagos.update');
        Route::delete('/{pago}', [PagoController::class, 'anular'])->name('pagos.anular');
    });

    // Flujo Individual
    Route::prefix('reservas_individual')->group(function () {
        Route::get('/create', [ReservaIndividualController::class, 'create'])->name('reservas_individual.create');
        Route::post('/store', [ReservaIndividualController::class, 'store'])->name('reservas_individual.store');
    });

    // Flujo Grupal
    Route::prefix('reservas_grupal')->group(function () {
        Route::get('/create', [ReservaGrupalController::class, 'create'])->name('reservas_grupal.create');
        Route::post('/store', [ReservaGrupalController::class, 'store'])->name('reservas_grupal.store');
    });
});




