<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Evento extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'eventos';

    protected $fillable = [
        'titulo',
        'fecha',
        'hora',
        'lugar',
        'categoria'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}