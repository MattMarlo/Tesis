<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoCliente extends Model
{
    use HasFactory;

    protected $table = 'grupos_clientes';

    //protected $primaryKey = 'id';

   

    protected $fillable = [
        'grupo_id',
        'cliente_id',
        'monto_asignado',
        'es_lider'
    ];

    

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }


    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}