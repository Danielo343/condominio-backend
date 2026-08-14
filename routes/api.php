<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControladorResidente;
use App\Http\Controllers\ControladorConfiguracion;
use App\Http\Controllers\ControladorChat;
use App\Http\Controllers\ControladorDashboard;
use App\Http\Controllers\ControladorDocumento;
use App\Http\Controllers\ControladorNotificacion;
use App\Http\Controllers\ControladorFactura;

// Rutas Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/recuperar-clave', [AuthController::class, 'solicitarCodigo']);
Route::post('/restablecer-clave', [AuthController::class, 'restablecerConCodigo']);

// Rutas Protegidas
Route::middleware('mongo.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']);
    Route::put('/perfil', [AuthController::class, 'actualizarPerfil']);
    Route::post('/cambiar-clave', [AuthController::class, 'cambiarPassword']);

    // Dashboard y Métricas
    Route::get('/dashboard/resumen', [ControladorDashboard::class, 'obtenerResumen']);
    Route::post('/dashboard/eventos', [ControladorDashboard::class, 'crearEvento']);
    Route::delete('/dashboard/eventos/{id}', [ControladorDashboard::class, 'eliminarEvento']);

    // Facturación y Cuotas de Pago
    Route::get('/facturas', [ControladorFactura::class, 'index']);
    Route::post('/facturas', [ControladorFactura::class, 'store']);
    Route::put('/facturas/{id}/estado', [ControladorFactura::class, 'cambiarEstado']);
    Route::delete('/facturas/{id}', [ControladorFactura::class, 'destroy']);

    // Notificaciones
    Route::get('/notificaciones', [ControladorNotificacion::class, 'index']);
    Route::post('/notificaciones/marcar-leidas', [ControladorNotificacion::class, 'marcarTodasLeidas']);

    // Configuración
    Route::get('/configuracion', [ControladorConfiguracion::class, 'obtenerConfiguracion']);
    Route::post('/configuracion', [ControladorConfiguracion::class, 'guardarConfiguracion']);

    // Residentes
    Route::get('/residentes', [ControladorResidente::class, 'index']);
    Route::post('/residentes', [ControladorResidente::class, 'store']);
    Route::put('/residentes/{id}', [ControladorResidente::class, 'update']);
    Route::delete('/residentes/{id}', [ControladorResidente::class, 'destroy']);

    // Documentos
    Route::get('/documentos', [ControladorDocumento::class, 'index']);
    Route::post('/documentos', [ControladorDocumento::class, 'store']);
    Route::delete('/documentos/{id}', [ControladorDocumento::class, 'destroy']);

    // Chat
    Route::get('/chat/mensajes', [ControladorChat::class, 'obtenerMensajes']);
    Route::post('/chat/mensajes', [ControladorChat::class, 'enviarMensaje']);
});