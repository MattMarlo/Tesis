<?php

namespace App\Services;

use App\Models\AlojamientoReserva;
use App\Models\GestionOperativa;
use App\Models\GestionOperativaViajero;
use App\Models\GuiaReserva;
use App\Models\TareaOperacionViaje;
use App\Models\User;
use App\Models\VueloReserva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EstadoTareaContextualService
{
    public function vincular(
        TareaOperacionViaje $tarea,
        Model $gestionable,
        ?User $usuario = null
    ): TareaOperacionViaje {
        $this->validarMismaOperacion(
            $tarea,
            $gestionable
        );

        $this->validarTipoCompatible(
            $tarea,
            $gestionable
        );

        $tarea->gestionable()->associate(
            $gestionable
        );

        $tarea->save();

        return $this->sincronizar(
            $tarea,
            $usuario
        );
    }

    public function desvincular(
        TareaOperacionViaje $tarea
    ): TareaOperacionViaje {
        $tarea->gestionable()->dissociate();

        $tarea->estado =
            TareaOperacionViaje::ESTADO_PENDIENTE;

        $tarea->completada_at = null;
        $tarea->completada_por_user_id = null;

        $tarea->save();

        return $tarea->fresh();
    }

    public function sincronizar(
        TareaOperacionViaje $tarea,
        ?User $usuario = null
    ): TareaOperacionViaje {
        $tareaActual = $tarea->fresh([
            'gestionable',
            'operacion.reserva',
        ]);

        if (!$tareaActual) {
            throw ValidationException::withMessages([
                'tarea' =>
                    'La tarea ya no existe.',
            ]);
        }

        /*
         * Una tarea omitida conserva su estado, aunque tenga
         * una gestión vinculada.
         */
        if ($tareaActual->estaOmitida()) {
            return $tareaActual;
        }

        /*
         * Si todavía no hay ningún registro operativo vinculado,
         * no modificamos manualmente su estado actual.
         */
        if (!$tareaActual->gestionable) {
            return $tareaActual;
        }

        $cantidadViajeros =
            $this->cantidadViajerosEsperados(
                $tareaActual
            );

        $estado = $this->resolverEstado(
            $tareaActual->gestionable,
            $cantidadViajeros
        );

        $datos = [
            'estado' => $estado,
        ];

        if (
            $estado ===
            TareaOperacionViaje::ESTADO_COMPLETADA
        ) {
            $datos['completada_at'] =
                $tareaActual->completada_at
                ?? now();

            $datos['completada_por_user_id'] =
                $tareaActual->completada_por_user_id
                ?? $usuario?->id;
        } else {
            $datos['completada_at'] = null;
            $datos['completada_por_user_id'] = null;
        }

        $tareaActual->update($datos);

        return $tareaActual->fresh([
            'gestionable',
        ]);
    }

    private function resolverEstado(
        Model $gestionable,
        int $cantidadViajeros
    ): string {
        return match (true) {
            $gestionable instanceof VueloReserva =>
                $this->estadoVuelo(
                    $gestionable,
                    $cantidadViajeros
                ),

            $gestionable instanceof AlojamientoReserva =>
                $this->estadoAlojamiento(
                    $gestionable,
                    $cantidadViajeros
                ),

            $gestionable instanceof GuiaReserva =>
                $this->estadoGuia(
                    $gestionable
                ),

            $gestionable instanceof GestionOperativa =>
                $this->estadoGestionGenerica(
                    $gestionable,
                    $cantidadViajeros
                ),

            default =>
                TareaOperacionViaje::ESTADO_PENDIENTE,
        };
    }

    private function estadoVuelo(
        VueloReserva $vuelo,
        int $cantidadViajeros
    ): string {
        if (
            $vuelo->estaCancelado()
            || $vuelo->estaPendiente()
        ) {
            return
                TareaOperacionViaje::ESTADO_PENDIENTE;
        }

        if (
            $vuelo->estaListoPara(
                $cantidadViajeros
            )
        ) {
            return
                TareaOperacionViaje::ESTADO_COMPLETADA;
        }

        return
            TareaOperacionViaje::ESTADO_EN_PROCESO;
    }

    private function estadoAlojamiento(
        AlojamientoReserva $alojamiento,
        int $cantidadViajeros
    ): string {
        if (
            $alojamiento->estaCancelado()
            || $alojamiento->estaPendiente()
        ) {
            return
                TareaOperacionViaje::ESTADO_PENDIENTE;
        }

        if (
            $alojamiento->estaListoPara(
                $cantidadViajeros
            )
        ) {
            return
                TareaOperacionViaje::ESTADO_COMPLETADA;
        }

        return
            TareaOperacionViaje::ESTADO_EN_PROCESO;
    }

    private function estadoGuia(
        GuiaReserva $guia
    ): string {
        if (
            $guia->estaCancelado()
            || $guia->estaPendiente()
        ) {
            return
                TareaOperacionViaje::ESTADO_PENDIENTE;
        }

        if ($guia->estaLista()) {
            return
                TareaOperacionViaje::ESTADO_COMPLETADA;
        }

        return
            TareaOperacionViaje::ESTADO_EN_PROCESO;
    }

    private function estadoGestionGenerica(
        GestionOperativa $gestion,
        int $cantidadViajeros
    ): string {
        if (
            $gestion->estaCancelada()
            || $gestion->estaPendiente()
        ) {
            return
                TareaOperacionViaje::ESTADO_PENDIENTE;
        }

        if (
            $gestion->estaEnProceso()
            || !$gestion->estaConfirmada()
        ) {
            return
                TareaOperacionViaje::ESTADO_EN_PROCESO;
        }

        /*
         * Una gestión confirmada debe cubrir como mínimo
         * la cantidad de viajeros de la reserva.
         */
        if (
            (int) $gestion->cantidad_viajeros
            < $cantidadViajeros
        ) {
            return
                TareaOperacionViaje::ESTADO_EN_PROCESO;
        }

        /*
         * Las gestiones que no necesitan boleto, entrada u
         * otro registro individual ya pueden completarse.
         */
        if (
            !$gestion->requiereDetalleIndividual()
        ) {
            return
                TareaOperacionViaje::ESTADO_COMPLETADA;
        }

        /*
         * Para trenes, entradas, seguros y servicios que
         * requieran detalle individual, verificamos que todos
         * los viajeros estén confirmados.
         */
        $cantidadConfirmados =
            $gestion->detallesViajeros()
                ->where(
                    'estado',
                    GestionOperativaViajero::ESTADO_CONFIRMADO
                )
                ->distinct()
                ->count('viajero_reserva_id');

        if (
            $cantidadConfirmados
            >= $cantidadViajeros
        ) {
            return
                TareaOperacionViaje::ESTADO_COMPLETADA;
        }

        return
            TareaOperacionViaje::ESTADO_EN_PROCESO;
    }

    private function cantidadViajerosEsperados(
        TareaOperacionViaje $tarea
    ): int {
        $reserva =
            $tarea->operacion?->reserva;

        if (!$reserva) {
            return 1;
        }

        $cantidadRegistrados =
            $reserva->viajerosReserva()
                ->count();

        if ($cantidadRegistrados > 0) {
            return $cantidadRegistrados;
        }

        return max(
            1,
            (int) $reserva->cantidad_viajeros
        );
    }

    private function validarMismaOperacion(
        TareaOperacionViaje $tarea,
        Model $gestionable
    ): void {
        $operacionGestionable = (int) (
            $gestionable->operacion_viaje_id
            ?? 0
        );

        if (
            $operacionGestionable
            !== (int) $tarea->operacion_viaje_id
        ) {
            throw ValidationException::withMessages([
                'gestionable' =>
                    'La gestión seleccionada pertenece a otra operación.',
            ]);
        }
    }

    private function validarTipoCompatible(
        TareaOperacionViaje $tarea,
        Model $gestionable
    ): void {
        $compatible = match (
            $tarea->tipo_gestion
        ) {
            TareaOperacionViaje::TIPO_VUELO =>
                $gestionable instanceof VueloReserva,

            TareaOperacionViaje::TIPO_ALOJAMIENTO =>
                $gestionable instanceof AlojamientoReserva,

            TareaOperacionViaje::TIPO_GUIA =>
                $gestionable instanceof GuiaReserva,

            TareaOperacionViaje::TIPO_TREN,
            TareaOperacionViaje::TIPO_TRASLADO,
            TareaOperacionViaje::TIPO_ENTRADA,
            TareaOperacionViaje::TIPO_ALIMENTACION,
            TareaOperacionViaje::TIPO_ACTIVIDAD_RESERVADA,
            TareaOperacionViaje::TIPO_SEGURO,
            TareaOperacionViaje::TIPO_OTRO =>
                $gestionable instanceof GestionOperativa
                && $gestionable->tipo
                    === $tarea->tipo_gestion,

            default => false,
        };

        if (!$compatible) {
            throw ValidationException::withMessages([
                'gestionable' =>
                    'El registro seleccionado no corresponde al tipo de gestión de la tarea.',
            ]);
        }
    }
}