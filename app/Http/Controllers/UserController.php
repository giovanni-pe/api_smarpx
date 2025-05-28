<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function showProfile(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();

        // Verificar si el usuario es cliente
        if ($user->hasRole('client')) {
            // Obtener el perfil de cliente a través de la tabla pivot
            $client = $user->clients()->first();  // Usamos 'first' porque un usuario solo tiene un cliente

            // Devolvemos el perfil del cliente
            return response()->json([
                'user' => $user,
                'client' => $client
            ]);
        }

        // Verificar si el usuario es paseador
        if ($user->hasRole('dog_walker')) {
            // Obtener el perfil de paseador a través de la tabla pivot
            $walker = $user->walkers()->first();  // Usamos 'first' porque un usuario solo tiene un paseador

            // Devolvemos el perfil del paseador
            return response()->json([
                'user' => $user,
                'walker' => $walker
            ]);
        }

        // Si el usuario no es cliente ni paseador
        return response()->json([
            'message' => 'No se ha encontrado un perfil para este usuario'
        ], 404);
    }
    /**
     * Solicitar eliminación de cuenta
     */
    public function requestAccountDeletion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Marcar usuario para eliminación
            $user->update([
                'is_pending_deletion' => true,
                'deletion_requested_at' => now(),
                'deletion_reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de eliminación procesada correctamente. La cuenta será eliminada en las próximas 24-48 horas.',
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar cuenta definitivamente
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Verificar contraseña
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contraseña incorrecta'
                ], 401);
            }

            // Eliminar datos relacionados
            $user->clients()->detach();
            $user->walkers()->detach();

            // Eliminar completamente
            $user->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta eliminada exitosamente. Todos los datos han sido eliminados.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cuenta'
            ], 500);
        }
    }

    /**
     * Obtener datos de usuario para eliminación (para cumplir requisitos)
     */
    public function getAccountData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)
                ->with(['clients', 'walkers'])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'has_client_profile' => $user->clients()->exists(),
                    'has_walker_profile' => $user->walkers()->exists(),
                    'is_pending_deletion' => $user->is_pending_deletion ?? false,
                    'deletion_requested_at' => $user->deletion_requested_at ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos'
            ], 500);
        }
    }
}
