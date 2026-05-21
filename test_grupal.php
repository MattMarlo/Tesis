<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = app(App\Services\ReservaService::class);

$datos = [
    'nombre_grupo' => 'Familia Gómez',
    'destino_id' => 1, // Assuming 1 exists
    'user_id' => 1,    // Assuming 1 exists
    'fecha_reserva' => '2026-03-22',
    'fecha_viaje' => '2026-07-10',
    'precio_total_viaje' => 3600.00,
    'integrantes' => [
        [
            'cliente_id' => 1,
            'monto_asignado' => 900.00,
            'es_lider' => true
        ],
        [
            'cliente_id' => 2,
            'monto_asignado' => 900.00,
            'es_lider' => false
        ],
        [
            'cliente_id' => 3,
            'monto_asignado' => 900.00,
            'es_lider' => false
        ],
        [
            'cliente_id' => 4,
            'monto_asignado' => 900.00,
            'es_lider' => false
        ]
    ]
];

try {
    $codigo = $service->guardarGrupal($datos);
    echo "OK GRUPAL $codigo\n";

    // Verify DB states
    $reserva = Illuminate\Support\Facades\DB::table('reservas')->where('codigo_reserva', $codigo)->first();
    echo "Reserva ID: {$reserva->id}, Cliente Lider: {$reserva->cliente_id}, Tipo: {$reserva->tipo}\n";

    $reservaGrupo = Illuminate\Support\Facades\DB::table('reservas_grupos')->where('reserva_id', $reserva->id)->first();
    echo "ReservaGrupo Grupo ID: {$reservaGrupo->grupo_id}\n";

    $grupo = Illuminate\Support\Facades\DB::table('grupos')->where('id', $reservaGrupo->grupo_id)->first();
    echo "Grupo Nombre: {$grupo->nombre_grupo}\n";

    $integrantes = Illuminate\Support\Facades\DB::table('grupos_clientes')->where('grupo_id', $grupo->id)->get();
    echo "Integrantes Count: " . $integrantes->count() . "\n";
    foreach ($integrantes as $int) {
        echo " - Cliente: {$int->cliente_id}, Lider: {$int->es_lider}, Monto: {$int->monto_asignado}\n";
    }

} catch (Exception $e) {
    echo 'ERR '.$e->getMessage()."\n";
}
