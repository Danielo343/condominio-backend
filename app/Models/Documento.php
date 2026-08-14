<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Documento extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'documentos';

    protected $fillable = [
        'titulo',
        'descripcion',
        'categoria',
        'nombre_archivo',
        'ruta_archivo',
        'tamanio_archivo',
        'extension'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}