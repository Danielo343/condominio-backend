<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Events\NuevoVotoEncuesta;

Route::get('/votar-prueba', function () {
    // Simulamos que alguien votó por "Reparar la alberca" y ahora tiene 15 votos
    NuevoVotoEncuesta::dispatch('Reparar la alberca', 15);
    return "¡Voto enviado por WebSockets exitosamente!";
});
