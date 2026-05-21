<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = app(App\Services\ReservaService::class);
$datos = [
    'cliente_id' => 1,
    'destino_id' => 1,
    'user_id' => 1,
    'estado' => 'pendiente',
    'fecha_reserva' => '2026-03-22',
    'fecha_viaje' => '2026-03-25',
    'precio_total_viaje' => 500.00,
    'monto_depositado' => 100.00,
    'metodo_pago' => 'Efectivo',
    'fecha_pago' => '2026-03-22'
];
try {
    $codigo = $service->guardarIndividual($datos);
    echo "OK $codigo\n";
} catch (Exception $e) {
    echo 'ERR '.$e->getMessage()."\n";
}
