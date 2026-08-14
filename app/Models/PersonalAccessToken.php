<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PersonalAccessToken extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'personal_access_tokens';

    protected $fillable = [
        'token_id',
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}