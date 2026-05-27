<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Encuesta;
use App\Models\Opcion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Administrador
        User::factory()->create([
            'name' => 'Admin Condominio',
            'email' => 'admin@condominio.com',
            'password' => Hash::make('password'),
            'rol' => 'admin',
        ]);

        // 2. Crear Inquilino de prueba
        User::factory()->create([
            'name' => 'José Daniel',
            'email' => 'daniel@condominio.com',
            'password' => Hash::make('password'),
            'rol' => 'inquilino',
        ]);

        // 3. Crear la Encuesta inicial para WebSockets
        $encuesta = Encuesta::create([
            'pregunta' => '¿En qué deberíamos invertir el fondo de reserva?',
            'is_activa' => true,
        ]);

        // 4. Crear opciones vinculadas a esa encuesta
        Opcion::create(['encuesta_id' => $encuesta->id, 'texto_opcion' => 'Reparar la alberca']);
        Opcion::create(['encuesta_id' => $encuesta->id, 'texto_opcion' => 'Pintar fachada']);
    }
}