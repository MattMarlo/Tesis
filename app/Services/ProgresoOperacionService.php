<?php

namespace App\Services;

use App\Models\OperacionViaje;
use App\Models\Reserva;

class ProgresoOperacionService
{
    public function calcular(OperacionViaje $operacion): array
    {
        $operacion->loadMissing([
            'reserva.destino',
            'reserva.cliente',
            'reserva.pagos',
            'reserva.grupo.clientes',
            'reserva.viajerosReserva',
            'vuelos.boletos',
            'alojamientos.asignacionesHabitacion',
            'alojamientos.habitaciones',
            'guias',
        ]);

        $reserva = $operacion->reserva;
        $familiaNueva = $reserva->grupo?->usaCategoriasFamiliares() ?? false;
        $personas = $this->personas($reserva, $familiaNueva);
        $esperados = $familiaNueva
            ? (int) $reserva->cantidad_viajeros
            : $personas->count();
        $identificados = $personas->count();
        $documentados = $personas->filter(
            fn ($p) => filled($p['tipo_documento']) && filled($p['documento'])
        )->count();
        $requierenBoleto = $personas->where('categoria', '!=', Reserva::TARIFA_INFANTE);
        $requierenHabitacion = $personas->where('categoria', '!=', Reserva::TARIFA_INFANTE);

        $servicios = collect($reserva->destino?->incluye ?? [])
            ->map(fn ($s) => mb_strtolower((string) $s))->implode(' ');
        $requiereVuelo = str_contains($servicios, 'vuelo') ||
            str_contains($servicios, 'aéreo') || str_contains($servicios, 'aereo') ||
            str_contains($servicios, 'boleto');
        $requiereAlojamiento = str_contains($servicios, 'hotel') ||
            str_contains($servicios, 'alojamiento') || str_contains($servicios, 'hospedaje');
        $requiereGuia = str_contains($servicios, 'guía') || str_contains($servicios, 'guia');

        $vuelos = $operacion->vuelos->where('estado', '!=', 'cancelado');
        $vuelosConfirmados = !$requiereVuelo ||
            ($vuelos->isNotEmpty() && $vuelos->every(fn ($v) => $v->estado === 'confirmado'));
        $conBoleto = $requiereVuelo && $vuelos->isNotEmpty()
            ? $requierenBoleto->filter(fn ($p) => $vuelos->every(
                fn ($v) => $v->boletos->contains(fn ($b) =>
                    $b->estado_emision === 'emitido' && $this->boletoEsDe($b, $p)
                )
            ))->count()
            : 0;
        $boletosPorVuelo = $vuelos->mapWithKeys(function ($vuelo) use ($requierenBoleto) {
            $emitidos = $requierenBoleto->filter(
                fn ($persona) => $vuelo->boletos->contains(
                    fn ($boleto) => $boleto->estado_emision === 'emitido' &&
                        $this->boletoEsDe($boleto, $persona)
                )
            )->count();

            return [$vuelo->id => [
                'actual' => $emitidos,
                'total' => $requierenBoleto->count(),
            ]];
        });

        $requierenAsiento = $requierenBoleto;
        $conAsiento = $requiereVuelo && $vuelos->isNotEmpty()
            ? $requierenAsiento->filter(fn ($p) => $vuelos->every(
                fn ($v) => $v->boletos->contains(fn ($b) =>
                    $b->estado_emision === 'emitido' && filled($b->asiento) &&
                    $this->boletoEsDe($b, $p)
                )
            ))->count()
            : 0;

        $alojamientos = $operacion->alojamientos->where('estado', '!=', 'cancelado');
        $distribucionHistorica = $operacion->estaCompleta() &&
            $alojamientos->isNotEmpty() &&
            $alojamientos->every(fn ($a) => $a->habitaciones->isEmpty());
        $conHabitacion = $distribucionHistorica
            ? $requierenHabitacion->count()
            : ($requiereAlojamiento && $alojamientos->isNotEmpty()
            ? $requierenHabitacion->filter(fn ($p) => $alojamientos->every(
                fn ($a) => $a->asignacionesHabitacion->contains(
                    fn ($asignacion) => $this->asignacionEsDe($asignacion, $p)
                )
            ))->count()
            : 0);
        $alojamientoConfirmado = !$requiereAlojamiento ||
            $alojamientos->isNotEmpty() && $alojamientos->every(fn ($a) => $a->estado === 'confirmado');
        $guiaConfirmado = !$requiereGuia ||
            $operacion->guias->contains('estado', 'confirmado');
        $habitacionesPorAlojamiento = $alojamientos->mapWithKeys(
            fn ($alojamiento) => [$alojamiento->id => [
                'actual' => $requierenHabitacion->filter(
                    fn ($persona) => $alojamiento->asignacionesHabitacion
                        ->contains(fn ($asignacion) =>
                            $this->asignacionEsDe($asignacion, $persona)
                        )
                )->count(),
                'total' => $requierenHabitacion->count(),
            ]]
        );

        $totalPagado = (float) $reserva->pagos->sum('monto_depositado');
        $saldo = max(0, (float) $reserva->precio_total_viaje - $totalPagado);

        $componentes = [
            $this->ratio($identificados, $esperados),
            $this->ratio($documentados, $esperados),
            $saldo <= 0 ? 1.0 : 0.0,
        ];
        if ($requiereVuelo) {
            $componentes[] = $vuelosConfirmados ? 1.0 : 0.0;
            $componentes[] = $this->ratio($conBoleto, $requierenBoleto->count());
            $componentes[] = $this->ratio($conAsiento, $requierenAsiento->count());
        }
        if ($requiereAlojamiento) {
            $componentes[] = $this->ratio($conHabitacion, $requierenHabitacion->count());
            $componentes[] = $alojamientoConfirmado ? 1.0 : 0.0;
        }
        if ($requiereGuia) {
            $componentes[] = $guiaConfirmado ? 1.0 : 0.0;
        }

        $motivos = [];
        if ($identificados < $esperados) {
            $motivos[] = $familiaNueva
                ? 'Faltan los datos personales de los acompañantes antes de completar la documentación del viaje.'
                : 'Faltan viajeros por identificar.';
        }
        if ($documentados < $esperados) $motivos[] = 'Faltan documentos de viajeros.';
        if ($requiereVuelo && $conBoleto < $requierenBoleto->count()) $motivos[] = 'Faltan boletos emitidos.';
        if (!$vuelosConfirmados) $motivos[] = 'Falta registrar y confirmar todos los vuelos requeridos.';
        if ($requiereVuelo && $conAsiento < $requierenAsiento->count()) $motivos[] = 'Faltan asientos para viajeros que los requieren.';
        if ($requiereAlojamiento && $conHabitacion < $requierenHabitacion->count()) $motivos[] = 'Faltan viajeros por distribuir en habitaciones.';
        if (!$alojamientoConfirmado) $motivos[] = 'Falta confirmar el alojamiento.';
        if (!$guiaConfirmado) $motivos[] = 'Falta confirmar el guía.';
        if ($saldo > 0) $motivos[] = 'La preparación puede continuar, pero el viaje no podrá marcarse como listo hasta completar el pago.';

        $puedeCompletar = $motivos === [];

        return [
            'viajeros_identificados' => ['actual' => $identificados, 'total' => $esperados],
            'documentos_registrados' => ['actual' => $documentados, 'total' => $esperados],
            'boletos_emitidos' => ['actual' => $conBoleto, 'total' => $requiereVuelo ? $requierenBoleto->count() : 0, 'aplica' => $requiereVuelo],
            'asientos_asignados' => ['actual' => $conAsiento, 'total' => $requiereVuelo ? $requierenAsiento->count() : 0, 'aplica' => $requiereVuelo],
            'viajeros_con_habitacion' => ['actual' => $conHabitacion, 'total' => $requiereAlojamiento ? $requierenHabitacion->count() : 0, 'aplica' => $requiereAlojamiento],
            'alojamiento_confirmado' => $alojamientoConfirmado,
            'guia_confirmado' => $guiaConfirmado,
            'estado_pago' => $reserva->estado_pago,
            'saldo_pendiente' => round($saldo, 2),
            'porcentaje_general' => (int) round(array_sum($componentes) / count($componentes) * 100),
            'motivos_pendientes' => $motivos,
            'puede_completar' => $puedeCompletar,
            'puede_notificar' => $puedeCompletar && $operacion->estaCompleta(),
            'boletos_por_vuelo' => $boletosPorVuelo,
            'habitaciones_por_alojamiento' => $habitacionesPorAlojamiento,
            'personas' => $personas,
            'familia_nueva' => $familiaNueva,
        ];
    }

    private function personas(Reserva $reserva, bool $familiaNueva)
    {
        if ($familiaNueva) {
            return $reserva->viajerosReserva->map(fn ($v) => [
                'tipo' => 'viajero', 'id' => $v->id, 'nombre' => $v->nombre_completo,
                'tipo_documento' => $v->tipo_documento, 'documento' => $v->documento,
                'documento_enmascarado' => $v->documento_enmascarado,
                'categoria' => $v->categoria_tarifa, 'edad' => $v->edad_al_viajar,
                'es_titular' => $v->es_titular,
                'requiere_boleto' => $v->categoria_tarifa !== Reserva::TARIFA_INFANTE,
                'requiere_habitacion' => $v->categoria_tarifa !== Reserva::TARIFA_INFANTE,
            ])->values();
        }

        $clientes = $reserva->esIndividual()
            ? collect([$reserva->cliente])->filter()
            : ($reserva->grupo?->clientes ?? collect());

        return $clientes->map(function ($c) use ($reserva) {
            $categoria = $reserva->esIndividual()
                ? $reserva->categoria_tarifa
                : $c->pivot?->categoria_tarifa;
            return [
                'tipo' => 'cliente', 'id' => $c->id, 'nombre' => $c->nombre_completo,
                'tipo_documento' => $c->tipo_documento, 'documento' => $c->documento,
                'documento_enmascarado' => $this->enmascarar($c->documento),
                'categoria' => $categoria, 'edad' => $reserva->esIndividual()
                    ? $reserva->edad_viajero : $c->pivot?->edad_al_viajar,
                'es_titular' => (int) $reserva->cliente_id === (int) $c->id,
                'requiere_boleto' => $categoria !== Reserva::TARIFA_INFANTE,
                'requiere_habitacion' => $categoria !== Reserva::TARIFA_INFANTE,
            ];
        })->values();
    }

    private function boletoEsDe($boleto, array $persona): bool
    {
        return $persona['tipo'] === 'viajero'
            ? (int) $boleto->viajero_reserva_id === (int) $persona['id']
            : (int) $boleto->cliente_id === (int) $persona['id'];
    }

    private function asignacionEsDe($asignacion, array $persona): bool
    {
        return $persona['tipo'] === 'viajero'
            ? (int) $asignacion->viajero_reserva_id === (int) $persona['id']
            : (int) $asignacion->cliente_id === (int) $persona['id'];
    }

    private function ratio(int $actual, int $total): float
    {
        return $total <= 0 ? 1.0 : min(1, $actual / $total);
    }

    private function enmascarar(?string $documento): string
    {
        if (!$documento) return 'Pendiente';
        $largo = mb_strlen($documento);
        return str_repeat('*', max(0, $largo - 4)) . mb_substr($documento, -4);
    }
}
