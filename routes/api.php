<?php

use App\Http\Controllers\Api\TelegramReservaController;
use App\Http\Controllers\Api\ConsultaReservaTelegramController;
use App\Http\Controllers\Api\SolicitudPrerreservaWhatsAppController;
use Illuminate\Support\Facades\Route;

Route::prefix('telegram')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get('/destinos', [TelegramReservaController::class, 'index']);
        Route::get('/destinos/{destino}', [TelegramReservaController::class, 'show']);
        Route::post('/cupos', [TelegramReservaController::class, 'cupos']);
        Route::post('/cotizar', [TelegramReservaController::class, 'cotizar']);
        Route::post('/prerreservas', [TelegramReservaController::class, 'store']);
        Route::post('/reservas/buscar',[ConsultaReservaTelegramController::class, 'buscar']);
        Route::post('/reservas/{reserva}/verificar',[ConsultaReservaTelegramController::class, 'verificar']);
        Route::post('/solicitudes-prerreserva',[SolicitudPrerreservaWhatsAppController::class, 'store'])->name('api.whatsapp.solicitudes-prerreserva.store');
    });
