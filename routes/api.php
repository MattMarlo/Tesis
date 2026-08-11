<?php

use App\Http\Controllers\Api\TelegramReservaController;
use App\Http\Controllers\Api\ConsultaReservaTelegramController;
use App\Http\Controllers\Api\SolicitudPrerreservaWhatsAppController;
use App\Http\Controllers\Api\WhatsAppConsentimientoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SolicitudAsesorWhatsAppController;

Route::prefix('telegram')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get('/destinos', [TelegramReservaController::class, 'index']);
        Route::get('/destinos/{destino}', [TelegramReservaController::class, 'show']);
        Route::post('/cupos', [TelegramReservaController::class, 'cupos']);
        Route::post('/cotizar', [TelegramReservaController::class, 'cotizar']);
        Route::post('/prerreservas', [TelegramReservaController::class, 'store']);
        Route::post('/reservas/buscar', [ConsultaReservaTelegramController::class, 'buscar']);
        Route::post('/reservas/{reserva}/verificar', [ConsultaReservaTelegramController::class, 'verificar']);
    });

Route::prefix('whatsapp')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get(
            '/consentimiento',
            [WhatsAppConsentimientoController::class, 'estado']
        )->name('api.whatsapp.consentimiento.estado');

        Route::post(
            '/consentimiento',
            [WhatsAppConsentimientoController::class, 'guardar']
        )->name('api.whatsapp.consentimiento.guardar');

        Route::post(
            '/solicitudes-prerreserva',
            [SolicitudPrerreservaWhatsAppController::class, 'store']
        )->name('api.whatsapp.solicitudes-prerreserva.store');

        Route::post(
            '/solicitudes-asesor',
            [
                SolicitudAsesorWhatsAppController::class,
                'store',
            ]
        )->name(
            'api.whatsapp.solicitudes-asesor.store'
        );
    });