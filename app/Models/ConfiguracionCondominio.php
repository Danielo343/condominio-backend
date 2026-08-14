<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ConfiguracionCondominio extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'configuracion_condominio';

    protected $fillable = [
        'clave_config',
        'nombre_condominio',
        'direccion',
        'administrador',
        'telefono',
        'cuota_mantenimiento',
        'moneda'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}