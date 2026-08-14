<?php

namespace App\Http\Controllers;

use App\Models\Residente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ControladorResidente extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Residente::query();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('unidad', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $residentes = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $residentes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar residentes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre'   => 'required|string|max:255',
                'unidad'   => 'required|string|max:100',
                'email'    => 'required|email|max:255',
                'telefono' => 'required|string|max:50',
                'estado'   => 'nullable|string|in:Activo,Inactivo',
                'rol'      => 'nullable|string|in:Administrador,Residente',
                'password' => 'nullable|string|min:6'
            ]);

            if (!isset($validated['estado'])) {
                $validated['estado'] = 'Activo';
            }

            $emailLimpio = strtolower(trim($validated['email']));
            $rolSeleccionado = $validated['rol'] ?? 'Residente';
            $passwordInicial = $validated['password'] ?? 'Condo1234';

            $residente = Residente::create([
                'nombre'   => $validated['nombre'],
                'unidad'   => $validated['unidad'],
                'email'    => $emailLimpio,
                'telefono' => $validated['telefono'],
                'estado'   => $validated['estado'],
                'rol'      => $rolSeleccionado
            ]);

            User::updateOrCreate(
                ['email' => $emailLimpio],
                [
                    'name'     => $validated['nombre'],
                    'password' => Hash::make($passwordInicial),
                    'role'     => $rolSeleccionado
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario registrado como ' . $rolSeleccionado . ' con contraseña: ' . $passwordInicial,
                'data' => $residente,
                'password_inicial' => $passwordInicial
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $residente = Residente::find($id);

            if (!$residente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Residente no encontrado.'
                ], 404);
            }

            $validated = $request->validate([
                'nombre'   => 'sometimes|required|string|max:255',
                'unidad'   => 'sometimes|required|string|max:100',
                'email'    => 'sometimes|required|email|max:255',
                'telefono' => 'sometimes|required|string|max:50',
                'estado'   => 'sometimes|required|string|in:Activo,Inactivo',
                'rol'      => 'nullable|string|in:Administrador,Residente'
            ]);

            if (isset($validated['email'])) {
                $validated['email'] = strtolower(trim($validated['email']));
            }

            $residente->update($validated);

            // Sincronizar o crear en la tabla de usuarios
            User::updateOrCreate(
                ['email' => $residente->email],
                [
                    'name'  => $validated['nombre'] ?? $residente->nombre,
                    'role'  => $validated['rol'] ?? ($residente->rol ?? 'Residente')
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Datos actualizados y sincronizados con éxito.',
                'data' => $residente
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $residente = Residente::find($id);

            if (!$residente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Registro no encontrado.'
                ], 404);
            }

            User::where('email', $residente->email)->delete();
            $residente->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario y cuenta eliminados correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}