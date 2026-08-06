<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->decimal('porcentaje_anticipo', 5, 2)
                ->default(30)
                ->after('precio_total_viaje');
            $table->decimal('monto_anticipo', 10, 2)
                ->nullable()
                ->after('porcentaje_anticipo');
            $table->timestamp('fecha_limite_anticipo')
                ->nullable()
                ->after('monto_anticipo');
            $table->date('fecha_vencimiento_saldo')
                ->nullable()
                ->after('fecha_limite_anticipo');
            $table->string('estado_cobranza', 30)
                ->default('pendiente_anticipo')
                ->after('estado_pago')
                ->index();
            $table->timestamp('anticipo_completado_at')
                ->nullable()
                ->after('estado_cobranza');
            $table->timestamp('saldo_completado_at')
                ->nullable()
                ->after('anticipo_completado_at');
            $table->string('version_politica_pago', 40)
                ->nullable()
                ->after('saldo_completado_at');
            $table->longText('politica_pago_aceptada')
                ->nullable()
                ->after('version_politica_pago');
            $table->timestamp('politica_aceptada_at')
                ->nullable()
                ->after('politica_pago_aceptada');
            $table->string('canal_aceptacion_politica', 30)
                ->nullable()
                ->after('politica_aceptada_at');
            $table->string('referencia_aceptacion_politica', 255)
                ->nullable()
                ->after('canal_aceptacion_politica');

            $table->string('tipo_cancelacion', 30)
                ->nullable()
                ->after('motivo_cancelacion');
            $table->boolean('cancelacion_automatica')
                ->default(false)
                ->after('tipo_cancelacion');
            $table->string('estado_reembolso', 30)
                ->default('no_aplica')
                ->after('cancelado_por_user_id')
                ->index();
            $table->decimal('monto_pagado_al_cancelar', 10, 2)
                ->nullable()
                ->after('estado_reembolso');
            $table->decimal('gastos_no_reembolsables', 10, 2)
                ->nullable()
                ->after('monto_pagado_al_cancelar');
            $table->decimal('monto_reembolsable', 10, 2)
                ->nullable()
                ->after('gastos_no_reembolsables');
            $table->text('detalle_gastos_no_reembolsables')
                ->nullable()
                ->after('monto_reembolsable');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->string('concepto', 30)
                ->default('abono')
                ->after('monto_depositado')
                ->index();
        });

        Schema::create('reservas_riesgo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')
                ->constrained('reservas')
                ->cascadeOnDelete();
            $table->string('tipo', 30)->index();
            $table->string('estado', 30)
                ->default('activa')
                ->index();
            $table->decimal('saldo_al_ingresar', 10, 2);
            $table->timestamp('fecha_ingreso');
            $table->timestamp('fecha_limite_regularizacion');
            $table->timestamp('fecha_resolucion')->nullable();
            $table->foreignId('resuelto_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['reserva_id', 'estado']);
        });

        /*
         * Los importes y fechas de reservas históricas se completan después
         * mediante reservas:evaluar-riesgo. Evitamos inferir aquí un contrato
         * distinto del que tuvo cada cliente.
         */
        $porcentaje = max(
            1,
            min(100, (float) config('reservas.porcentaje_anticipo', 30))
        );
        $diasSaldo = max(
            0,
            (int) config('reservas.dias_antes_saldo_final', 30)
        );
        $diasAnticipo = max(
            0,
            (int) config('reservas.dias_para_pagar_anticipo', 3)
        );

        DB::table('reservas')
            ->orderBy('id')
            ->chunkById(100, function ($reservas) use (
                $porcentaje,
                $diasSaldo,
                $diasAnticipo
            ) {
                foreach ($reservas as $reserva) {
                    $fechaReserva = Carbon::parse(
                        $reserva->fecha_reserva ?? $reserva->created_at
                    )->startOfDay();
                    $fechaViaje = Carbon::parse(
                        $reserva->fecha_viaje
                    )->startOfDay();
                    $vencimiento = $fechaViaje
                        ->copy()
                        ->subDays($diasSaldo);
                    $limiteAnticipo = $fechaReserva
                        ->copy()
                        ->addDays($diasAnticipo)
                        ->endOfDay();

                    if ($vencimiento->lte($limiteAnticipo)) {
                        $limiteAnticipo = $fechaReserva->gte($vencimiento)
                            ? $fechaReserva->copy()->endOfDay()
                            : $vencimiento->copy()->endOfDay();
                    }

                    $total = (float) $reserva->precio_total_viaje;
                    $anticipo = $fechaReserva->gte($vencimiento)
                        ? $total
                        : round($total * ($porcentaje / 100), 2);

                    DB::table('reservas')
                        ->where('id', $reserva->id)
                        ->update([
                            'porcentaje_anticipo' => $porcentaje,
                            'monto_anticipo' => $anticipo,
                            'fecha_limite_anticipo' => $limiteAnticipo,
                            'fecha_vencimiento_saldo' => $vencimiento
                                ->toDateString(),
                            'estado_cobranza' =>
                                $reserva->estado === 'cancelada'
                                    ? 'cancelada'
                                    : 'pendiente_anticipo',
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas_riesgo');

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(['concepto']);
            $table->dropColumn('concepto');
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['estado_cobranza']);
            $table->dropIndex(['estado_reembolso']);
            $table->dropColumn([
                'porcentaje_anticipo',
                'monto_anticipo',
                'fecha_limite_anticipo',
                'fecha_vencimiento_saldo',
                'estado_cobranza',
                'anticipo_completado_at',
                'saldo_completado_at',
                'version_politica_pago',
                'politica_pago_aceptada',
                'politica_aceptada_at',
                'canal_aceptacion_politica',
                'referencia_aceptacion_politica',
                'tipo_cancelacion',
                'cancelacion_automatica',
                'estado_reembolso',
                'monto_pagado_al_cancelar',
                'gastos_no_reembolsables',
                'monto_reembolsable',
                'detalle_gastos_no_reembolsables',
            ]);
        });
    }
};
