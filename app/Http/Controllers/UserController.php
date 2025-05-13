<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Mostrar el perfil del usuario.
     * 
     * Dependiendo de si el usuario es cliente o paseador, 
     * consultaremos la tabla pivot correspondiente.
     *
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Obtener el perfil del usuario autenticado",
     *     tags={"Usuario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Perfil del usuario")
     * )
     */
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
}
