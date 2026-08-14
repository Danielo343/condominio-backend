<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionCondominio;
use Illuminate\Http\Request;

class ControladorConfiguracion extends Controller
{
    public function obtenerConfiguracion()
    {
        try {
            $config = ConfiguracionCondominio::where('clave_config', 'general_config')->first();

            if (!$config) {
                $config = ConfiguracionCondominio::create([
                    'clave_config'        => 'general_config',
                    'nombre_condominio'   => 'CondoMaster Pro Residencial',
                    'direccion'           => 'Av. Universidad 123, Jalisco',
                    'administrador'       => 'José Daniel Vázquez',
                    'telefono'            => '3312345678',
                    'cuota_mantenimiento' => 1500,
                    'moneda'              => 'MXN'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $config
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarConfiguracion(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre_condominio'   => 'required|string|max:255',
                'direccion'           => 'required|string|max:255',
                'administrador'       => 'required|string|max:255',
                'telefono'            => 'required|string|max:50',
                'cuota_mantenimiento' => 'required|numeric',
                'moneda'              => 'required|string|max:10'
            ]);

            $config = ConfiguracionCondominio::where('clave_config', 'general_config')->first();

            if ($config) {
                $config->update($validated);
            } else {
                $validated['clave_config'] = 'general_config';
                $config = ConfiguracionCondominio::create($validated);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Configuración del condominio guardada correctamente.',
                'data' => $config
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar configuración: ' . $e->getMessage()
            ], 500);
        }
    }
}