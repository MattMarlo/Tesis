<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ReservaService;

$service = app(ReservaService::class);
$datos = [
    'cliente_id' => 1,
    'destino_id' => 1,
    'user_id' => 1,
    'estado' => 'pendiente',
    'fecha_reserva' => '2026-03-22',
    'fecha_viaje' => '2026-03-25',
    'precio_total_viaje' => 500.00,
    'monto_depositado' => 100.00,
    'metodo_pago' => 'efectivo',
    'fecha_pago' => '2026-03-22'
];

try {
    $codigo = $service->guardarIndividual($datos);
    echo 'Reserva creada con código: ' . $codigo . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}