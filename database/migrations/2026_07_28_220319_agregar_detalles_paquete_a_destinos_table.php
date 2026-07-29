<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la información necesaria para gestionar
     * y publicar paquetes turísticos completos.
     */
    public function up(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            $table->string('nombre_paquete', 150)
                ->nullable()
                ->after('id');

            $table->string('slug', 180)
                ->nullable()
                ->unique()
                ->after('nombre_paquete');

            $table->string('ciudad_destino', 100)
                ->nullable()
                ->after('pais');

            $table->string('categoria', 100)
                ->nullable()
                ->after('ciudad_destino');

            $table->text('descripcion_corta')
                ->nullable()
                ->after('categoria');

            $table->longText('descripcion')
                ->nullable()
                ->after('descripcion_corta');

            $table->string('ciudad_salida', 150)
                ->nullable()
                ->after('descripcion');

            $table->date('fecha_salida')
                ->nullable()
                ->after('ciudad_salida');

            $table->date('fecha_regreso')
                ->nullable()
                ->after('fecha_salida');

            $table->unsignedInteger('noches')
                ->nullable()
                ->after('dias');

            $table->string('aerolinea', 120)
                ->nullable()
                ->after('noches');

            $table->string('hotel', 150)
                ->nullable()
                ->after('aerolinea');

            $table->char('moneda', 3)
                ->default('USD')
                ->after('precio');

            $table->decimal('precio_promocional', 10, 2)
                ->nullable()
                ->after('moneda');

            $table->json('incluye')
                ->nullable()
                ->after('capacidad');

            $table->json('no_incluye')
                ->nullable()
                ->after('incluye');

            $table->json('itinerario')
                ->nullable()
                ->after('no_incluye');

            $table->longText('condiciones')
                ->nullable()
                ->after('itinerario');

            $table->enum(
                'estado_publicacion',
                [
                    'borrador',
                    'publicado',
                    'no_disponible',
                ]
            )
                ->default('borrador')
                ->after('condiciones');

            $table->boolean('destacado')
                ->default(false)
                ->after('estado_publicacion');
        });
    }

    /**
     * Elimina únicamente las columnas agregadas
     * por esta migración.
     */
    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            $table->dropUnique(['slug']);

            $table->dropColumn([
                'nombre_paquete',
                'slug',
                'ciudad_destino',
                'categoria',
                'descripcion_corta',
                'descripcion',
                'ciudad_salida',
                'fecha_salida',
                'fecha_regreso',
                'noches',
                'aerolinea',
                'hotel',
                'moneda',
                'precio_promocional',
                'incluye',
                'no_incluye',
                'itinerario',
                'condiciones',
                'estado_publicacion',
                'destacado',
            ]);
        });
    }
};