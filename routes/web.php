<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\PreReservaController;
use App\Models\Destino;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ReservaIndividualController;
use App\Http\Controllers\ReservaGrupalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TestimonioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperacionViajeController;
use App\Http\Controllers\VueloReservaController;
use App\Http\Controllers\BoletoVueloController;
use App\Http\Controllers\AlojamientoReservaController;
use App\Http\Controllers\GuiaReservaController;
use App\Http\Middleware\CheckUserPermission;
use App\Models\Testimonio;

Route::get('/', function () {
    $destinos = Destino::where('estado_publicacion', 'publicado')
        ->whereNotNull('imagen')
        ->orderByDesc('destacado')
        ->orderBy('fecha_salida')
        ->get();

    $destacados = $destinos
        ->where('destacado', true)
        ->values();

    $categorias = $destinos
        ->pluck('categoria')
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $testimonios = Testimonio::publicados()->get();

    return view('loading', compact(
        'destinos',
        'destacados',
        'categorias',
        'testimonios'
    ));
});

// Detalle público de cada paquete turístico
Route::get('/paquetes/{slug}', [DestinoController::class, 'detalle'])
    ->name('paquetes.detalle');

// Endpoint público para recibir pre-reservas desde n8n (POST JSON)
Route::post(
    '/prereservas/webhook',
    [
        PreReservaController::class,
        'storeFromWebhook',
    ]
)->name('prereservas.webhook');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.process');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('auth')->group(function() {
    Route::get(
        '/main',
        [DashboardController::class, 'index']
    )->name('main');

    Route::prefix('usuarios')
        ->middleware('check.permission:usuarios.administrar')
        ->group(function () {
            Route::get('/',[UserController::class, 'index'])->name('usuarios');
            Route::get('/create',[UserController::class, 'create'])->name('usuarios.create');
            Route::post('/store',[UserController::class, 'store'])->name('usuarios.store');
            Route::get('/edit/{id}',[UserController::class, 'edit'])->name('usuarios.edit');
            Route::put('/update/{id}',[UserController::class, 'update'])->name('usuarios.update');
            Route::delete('/destroy/{id}',[UserController::class, 'destroy'])->name('usuarios.destroy');
        }
    );

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
        Route::get('/',[ReservaController::class, 'index'])->name('reservas');
        Route::get('/{reserva}/detalle',[ReservaController::class, 'detalleJson'])->name('reservas.detalle');
        Route::patch('/{reserva}/cancelar',[ReservaController::class, 'cancelar'])->name('reservas.cancelar');
    });

    Route::prefix('operaciones')
        ->name('operaciones.')
        ->group(function () {
            Route::get('/',[OperacionViajeController::class, 'index'])->name('index');
            Route::get('/reserva/{id}',[OperacionViajeController::class, 'show'])->name('show');
            Route::put('/expediente/{operacion}',[OperacionViajeController::class, 'update'])->name('update');
            Route::post('/expediente/{operacion}/vuelos',[VueloReservaController::class, 'store'])->name('vuelos.store');
            Route::put('/vuelos/{vuelo}',[VueloReservaController::class, 'update'])->name('vuelos.update');
            Route::delete('/vuelos/{vuelo}',[VueloReservaController::class, 'destroy'])->name('vuelos.destroy');
            Route::post('/vuelos/{vuelo}/boletos',[BoletoVueloController::class, 'store'])->name('boletos.store');
            Route::delete('/boletos/{boleto}',[BoletoVueloController::class, 'destroy'])->name('boletos.destroy');
            Route::post('/expediente/{operacion}/alojamientos',[AlojamientoReservaController::class, 'store'])->name('alojamientos.store');
            Route::put('/alojamientos/{alojamiento}',[AlojamientoReservaController::class, 'update'])->name('alojamientos.update');
            Route::delete('/alojamientos/{alojamiento}',[AlojamientoReservaController::class, 'destroy'])->name('alojamientos.destroy');
            Route::post('/expediente/{operacion}/guias',[GuiaReservaController::class, 'store'])->name('guias.store');
            Route::put('/guias/{guia}',[GuiaReservaController::class, 'update'])->name('guias.update');
            Route::delete('/guias/{guia}',[GuiaReservaController::class, 'destroy'])->name('guias.destroy');
        });

    Route::prefix('pagos')->group(function () {
        Route::get('/', [PagoController::class, 'index'])->name('pagos');
        Route::post('/store', [PagoController::class, 'store'])->name('pagos.store');
        Route::get('/grupo/{reserva}', [PagoController::class, 'showGrupoDetails'])->name('pagos.grupo');
        Route::get('/reserva/{reservaId}/pagos-lista', [PagoController::class, 'listaPagosReserva'])->name('pagos.lista');
        Route::get('/{pago}/auditoria', [PagoController::class, 'auditoria'])->name('pagos.auditoria');
        Route::put('/{pago}', [PagoController::class, 'update'])->name('pagos.update');
        Route::delete('/{pago}', [PagoController::class, 'anular'])->name('pagos.anular');
    });

    // Flujo Individual
    Route::prefix('reservas_individual')->group(function () {
        Route::get('/create',[ReservaIndividualController::class, 'create'])->name('reservas_individual.create');
        Route::post('/store',[ReservaIndividualController::class, 'store'])->name('reservas_individual.store');
        Route::get('/{id}/edit',[ReservaIndividualController::class, 'edit'])->name('reservas_individual.edit');
        Route::put('/{id}',[ReservaIndividualController::class, 'update'])->name('reservas_individual.update');
    });

    // Flujo Grupal
    Route::prefix('reservas_grupal')->group(function () {
        Route::get('/create',[ReservaGrupalController::class, 'create'])->name('reservas_grupal.create');
        Route::post('/store',[ReservaGrupalController::class, 'store'])->name('reservas_grupal.store');
        Route::get('/{id}/edit', [ReservaGrupalController::class, 'edit'])->name('reservas_grupal.edit');
        Route::put('/{id}',[ReservaGrupalController::class, 'update'])->name('reservas_grupal.update');
    });

    Route::prefix('reportes')
        ->name('reportes.')
        ->group(function () {
            Route::get('/ingresos',[ReporteController::class,'ingresosMensuales',])->name('ingresos');
        }
    );
    
    //testimonios
    Route::prefix('testimonios')->group(function () {
        Route::get('/',[TestimonioController::class, 'index'])->name('testimonios.index');
        Route::get('/create',[TestimonioController::class, 'create'])->name('testimonios.create');
        Route::post('/store',[TestimonioController::class, 'store'])->name('testimonios.store');
        Route::get('/{testimonio}/edit',[TestimonioController::class, 'edit'])->name('testimonios.edit');
        Route::put('/{testimonio}',[TestimonioController::class, 'update'])->name('testimonios.update');
        Route::delete('/{testimonio}',[TestimonioController::class, 'destroy'])->name('testimonios.destroy');
    });

    // Pre-reservas (administración)
    Route::prefix('prereservas')->group(function () {
        Route::get('/check',[PreReservaController::class, 'checkExistence'])->name('prereservas.check');
        Route::get('/',[PreReservaController::class, 'index'])->name('prereservas.index');
        Route::get('/{id}/editar',[PreReservaController::class, 'edit'])->name('prereservas.edit');
        Route::patch('/{id}',[PreReservaController::class, 'update'])->name('prereservas.update');
        Route::post('/{id}/convertir',[PreReservaController::class, 'convertToReserva'])->name('prereservas.convertir');
        Route::delete('/{id}',[PreReservaController::class, 'destroy'])->name('prereservas.destroy');
    });
});





