<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notificacion extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notificaciones';

    protected $fillable = [
        'titulo',
        'mensaje',
        'tipo',
        'leido_por'
    ];

    protected $casts = [
        'leido_por'  => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}