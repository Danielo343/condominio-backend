<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Events\NuevoVotoEncuesta;
use App\Http\Controllers\AuthController;

Route::post('/votar', function (Request $request) {
    // 1. Recibimos la opción que el usuario eligió en Vue
    $opcionElegida = $request->input('opcion');
    
    // 2. Aquí en el futuro guardarás el voto en tu base de datos (PostgreSQL)
    // Por ahora, simularemos que al votar, esa opción llega a 25 votos
    $votosSimulados = rand(15, 50); 
    
    // 3. Disparamos el evento de WebSockets para todos los navegadores
    NuevoVotoEncuesta::dispatch($opcionElegida, $votosSimulados);

    return response()->json(['mensaje' => 'Voto registrado con éxito']);
});



// Ruta para que Vue envíe las credenciales
Route::post('/login', [AuthController::class, 'login']);
