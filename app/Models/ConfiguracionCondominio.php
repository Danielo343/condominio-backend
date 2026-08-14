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
        'capacidad_total',
        'dia_corte',
        'banco',
        'clabe_interbancaria',
        'beneficiario',
        'moneda'
    ];

    protected $casts = [
        'cuota_mantenimiento' => 'float',
        'capacidad_total'     => 'integer',
        'dia_corte'           => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];
}