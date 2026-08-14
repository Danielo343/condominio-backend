<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Eliminar registros anteriores con hash duplicado
        User::where('email', 'admin@lospinos.com')->delete();

        // Crear el usuario administrador con el hash correcto
        User::create([
            'name'     => 'Administrador CondoMaster',
            'email'    => 'admin@lospinos.com',
            'password' => Hash::make('12345678'),
            'role'     => 'Administrador'
        ]);
    }
}