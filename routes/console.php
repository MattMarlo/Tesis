<?php

use App\Services\GestionRiesgoReservaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservas:evaluar-riesgo', function () {
    $resultado = app(GestionRiesgoReservaService::class)
        ->evaluarTodas();

    $this->info('Reservas evaluadas: ' . $resultado['evaluadas']);
    $this->info('Reservas en riesgo: ' . $resultado['en_riesgo']);
    $this->info('Canceladas: ' . $resultado['canceladas']);
    $this->info('En revisión: ' . $resultado['en_revision']);
    $this->comment('Omitidas sin aceptación: ' . $resultado['omitidas_sin_aceptacion']);
})->describe('Evalúa reservas en riesgo y cancela automáticamente las que exceden el plazo de gracia de pago');
