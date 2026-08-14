<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Factura extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'facturas';

    protected $fillable = [
        'folio',
        'residente_id',
        'nombre_residente',
        'email_residente',
        'unidad',
        'concepto',
        'monto',
        'estado', // 'Pagado', 'Pendiente', 'Vencido'
        'fecha_emision',
        'fecha_vencimiento',
        'fecha_pago'
    ];

    protected $casts = [
        'monto'      => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}