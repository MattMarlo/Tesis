<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionLanding extends Model
{
    protected $table = 'configuracion_ubicacion';

    protected $fillable = [
        'localidad',
        'direccion',
        'consulta_mapa',
        'enlace_mapa',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }

    public static function valoresPredeterminados(): array
    {
        return [
            'localidad' => 'Salcedo',
            'direccion' => 'Salcedo, Cotopaxi, Ecuador',
            'consulta_mapa' => 'Passion Travel, Salcedo, Cotopaxi, Ecuador',
            'enlace_mapa' => 'https://maps.app.goo.gl/BcySuXQbntDDHPZY8',
        ];
    }

    public static function actual(): self
    {
        return static::query()->first()
            ?? new static(static::valoresPredeterminados());
    }

    public function urlMapa(): string
    {
        if ($this->tieneCoordenadas()) {
            return 'https://www.google.com/maps/dir/?api=1&destination='.
                rawurlencode($this->coordenadas());
        }

        return $this->enlace_mapa;
    }

    public function urlMapaEmbebido(): string
    {
        if ($this->tieneCoordenadas()) {
            return 'https://www.google.com/maps?q='.
                rawurlencode($this->coordenadas()).
                '&z=17&output=embed';
        }

        return 'https://www.google.com/maps?q='.
            rawurlencode($this->consulta_mapa).
            '&output=embed';
    }

    private function tieneCoordenadas(): bool
    {
        return $this->latitud !== null &&
            $this->longitud !== null;
    }

    private function coordenadas(): string
    {
        return $this->latitud.','.$this->longitud;
    }
}
