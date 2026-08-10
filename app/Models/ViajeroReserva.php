<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViajeroReserva extends Model
{
    protected $table =
        'viajeros_reserva';

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'nombres',
        'apellidos',
        'tipo_documento',
        'documento',
        'fecha_nacimiento',
        'edad_al_viajar',
        'categoria_tarifa',
        'es_titular',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' =>
                'date',

            'edad_al_viajar' =>
                'integer',

            'es_titular' =>
                'boolean',
        ];
    }

    /**
     * Reserva a la que pertenece el viajero.
     */
    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    /**
     * Cliente relacionado, cuando el viajero está
     * registrado también como cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    /**
     * Boletos aéreos individuales del viajero.
     */
    public function boletos()
    {
        return $this->hasMany(
            BoletoVuelo::class,
            'viajero_reserva_id'
        );
    }

    /**
     * Habitaciones asignadas al viajero.
     */
    public function asignacionesHabitacion()
    {
        return $this->hasMany(
            AsignacionHabitacion::class,
            'viajero_reserva_id'
        );
    }

    /**
     * Detalles individuales dentro de las gestiones genéricas.
     *
     * Aquí se almacenan el número de boleto, asiento,
     * referencia, estado y restricciones de la persona.
     */
    public function detallesGestionesOperativas()
    {
        return $this->hasMany(
            GestionOperativaViajero::class,
            'viajero_reserva_id'
        );
    }

    /**
     * Gestiones genéricas en las que participa el viajero.
     */
    public function gestionesOperativas()
    {
        return $this->belongsToMany(
            GestionOperativa::class,
            'gestion_operativa_viajeros',
            'viajero_reserva_id',
            'gestion_operativa_id'
        )
            ->withPivot([
                'id',
                'numero_documento',
                'asiento',
                'referencia_individual',
                'estado',
                'restricciones',
                'observaciones',
            ])
            ->withTimestamps();
    }

    /**
     * Nombre completo utilizado en los formularios
     * y resúmenes de la operación.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim(
            $this->nombres
            . ' '
            . $this->apellidos
        );
    }

    /**
     * Documento parcialmente oculto.
     */
    public function getDocumentoEnmascaradoAttribute(): string
    {
        if (!$this->documento) {
            return 'Pendiente';
        }

        $longitud = mb_strlen(
            $this->documento
        );

        $visibles = min(
            4,
            $longitud
        );

        return str_repeat(
            '*',
            max(
                0,
                $longitud - $visibles
            )
        ) . mb_substr(
            $this->documento,
            -$visibles
        );
    }

    /**
     * Verifica que tenga los datos mínimos para emitir
     * boletos, entradas o documentos individuales.
     */
    public function tieneDocumentoCompleto(): bool
    {
        return filled(
            $this->tipo_documento
        ) && filled(
            $this->documento
        );
    }

    /**
     * Indica si ya cuenta con boleto para un vuelo.
     */
    public function tieneBoletoPara(
        int $vueloReservaId
    ): bool {
        return $this->boletos()
            ->where(
                'vuelo_reserva_id',
                $vueloReservaId
            )
            ->exists();
    }

    /**
     * Indica si está asignado a una gestión genérica.
     */
    public function estaEnGestionOperativa(
        int $gestionOperativaId
    ): bool {
        return $this
            ->detallesGestionesOperativas()
            ->where(
                'gestion_operativa_id',
                $gestionOperativaId
            )
            ->exists();
    }
}