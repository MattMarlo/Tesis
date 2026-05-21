<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';

    protected $fillable = [
        'nombre_grupo',
        'descripcion'
    ];

    /**
     * RELACIÓN CORREGIDA: Hacia la reserva
     * Como usamos una tabla puente (reserva_grupo), la relación es a través de ella.
     */
    public function reserva()
    {
        // Un grupo está vinculado a una reserva mediante la tabla puente
        return $this->hasOne(ReservaGrupo::class, 'grupo_id');
    }

    /**
     * RELACIÓN DE CLIENTES:
     * Añadimos withTimestamps() si tus tablas tienen created_at/updated_at
     */
    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'grupo_cliente', 'grupo_id', 'cliente_id')
                    ->withPivot('monto_asignado', 'es_lider')
                    ->withTimestamps();
    }
}