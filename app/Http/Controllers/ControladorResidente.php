<?php

namespace App\Http\Controllers;

use App\Models\Residente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
                'nombre' => [
                    'required',
                    'string',
                    'min:5',
                    'max:100',
                    'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
                    function ($attribute, $value, $fail) {
                        $palabras = array_filter(explode(' ', trim($value)));
                        if (count($palabras) < 2) {
                            $fail('Debes ingresar al menos un nombre y un apellido.');
                        }
                    },
                ],
                'unidad'   => 'required|string|min:4|max:50',
                'email'    => 'required|email|max:255',
                'telefono' => 'required|string|regex:/^[0-9]{10}$/',
                'estado'   => 'nullable|string|in:Activo,Inactivo',
                'rol'      => 'nullable|string|in:Administrador,Residente',
                'password' => 'nullable|string|min:6'
            ], [
                'nombre.required'   => 'El nombre completo es obligatorio.',
                'nombre.regex'      => 'El nombre solo debe contener letras.',
                'unidad.required'   => 'La unidad o departamento es obligatorio.',
                'email.required'    => 'El correo electrónico es obligatorio.',
                'email.email'       => 'Ingresa un formato de correo válido.',
                'telefono.required' => 'El teléfono es obligatorio.',
                'telefono.regex'    => 'El teléfono debe contener exactamente 10 dígitos numéricos.'
            ]);

            $emailLimpio = strtolower(trim($validated['email']));

            if (Residente::where('email', $emailLimpio)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este correo electrónico ya se encuentra registrado.'
                ], 422);
            }

            $rolSeleccionado = $validated['rol'] ?? 'Residente';
            $passwordInicial = $validated['password'] ?? 'Condo1234';

            $residente = Residente::create([
                'nombre'   => trim($validated['nombre']),
                'unidad'   => trim($validated['unidad']),
                'email'    => $emailLimpio,
                'telefono' => trim($validated['telefono']),
                'estado'   => $validated['estado'] ?? 'Activo',
                'rol'      => $rolSeleccionado
            ]);

            User::updateOrCreate(
                ['email' => $emailLimpio],
                [
                    'name'     => trim($validated['nombre']),
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
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar: ' . $e->getMessage()
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
                    'message' => 'Registro no encontrado.'
                ], 404);
            }

            $validated = $request->validate([
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'min:5',
                    'max:100',
                    'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
                    function ($attribute, $value, $fail) {
                        $palabras = array_filter(explode(' ', trim($value)));
                        if (count($palabras) < 2) {
                            $fail('Debes ingresar al menos un nombre y un apellido.');
                        }
                    },
                ],
                'unidad'   => 'sometimes|required|string|min:4|max:50',
                'email'    => 'sometimes|required|email|max:255',
                'telefono' => 'sometimes|required|string|regex:/^[0-9]{10}$/',
                'estado'   => 'sometimes|required|string|in:Activo,Inactivo',
                'rol'      => 'nullable|string|in:Administrador,Residente'
            ], [
                'nombre.regex'   => 'El nombre solo debe contener letras.',
                'telefono.regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.'
            ]);

            if (isset($validated['email'])) {
                $validated['email'] = strtolower(trim($validated['email']));
            }

            $residente->update($validated);

            User::updateOrCreate(
                ['email' => $residente->email],
                [
                    'name'  => $validated['nombre'] ?? $residente->nombre,
                    'role'  => $validated['rol'] ?? ($residente->rol ?? 'Residente')
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Datos actualizados con éxito.',
                'data' => $residente
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
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