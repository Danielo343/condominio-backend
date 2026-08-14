<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MensajeChat extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'mensajes_chat';

    protected $fillable = [
        'user_id',
        'nombre_usuario',
        'mensaje',
        'hora'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}