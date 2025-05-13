<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\Walker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Dog Walker API",
 *     description="Documentación de la API para gestión de clientes, paseadores y admins.",
 *     @OA\Contact(
 *         email="soporte@dogwalker.com"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Registrar un nuevo usuario",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "role"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", example="secret123"),
     *             @OA\Property(property="role", type="string", example="client")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Usuario registrado correctamente"),
     *     @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => ['required', Rule::in(['client', 'dog_walker', 'admin'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'is_approved' => $request->role === 'dog_walker' ? false : true,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Usuario registrado con éxito',
            'user'    => $user,
        ]);
    }

    public function login(Request $request)
    {
        // Validación de los datos de la solicitud
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Buscar el usuario por correo electrónico
        $user = User::where('email', $request->email)->first();

        // Verificar si el usuario existe y la contraseña es válida
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Verificar si el usuario es un paseador y si su cuenta está aprobada
        if ($user->hasRole('dog_walker') && ! $user->is_approved) {
            return response()->json(['error' => 'Tu cuenta de paseador aún no ha sido aprobada.'], 403);
        }

        // Generar el token de acceso
        $token = $user->createToken('accessToken')->accessToken;

        // Preparar la respuesta
        $response = [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
            'roles'        => $user->getRoleNames(),
        ];

        // Si el usuario es un cliente, agregar los datos del cliente
        if ($user->hasRole('client')) {
            $client = $user->clients()->first();  // Obtener el perfil del cliente a través de la tabla pivot
            $response['client'] = $client;
        }

        // Si el usuario es un paseador, agregar los datos del paseador
        if ($user->hasRole('dog_walker')) {
            $walker = $user->walkers()->first();  // Obtener el perfil del paseador a través de la tabla pivot
            $response['walker'] = $walker;
        }

        // Retornar la respuesta con los datos del perfil
        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Obtener el perfil del usuario autenticado",
     *     tags={"Usuario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Perfil del usuario")
     * )
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user'  => $user,
            'roles' => $user->getRoleNames(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Cerrar sesión",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada correctamente")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function registerProfile(Request $request)
    {
        // Validación de los datos de la solicitud
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => ['required', Rule::in(['client', 'dog_walker', 'admin'])],
            'experience' => 'nullable|string', // Solo si es paseador
        ]);

        // Si hay errores de validación, retornamos la respuesta correspondiente
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        // Creación del usuario
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'is_approved' => $request->role === 'dog_walker' ? false : true, // Si es paseador, no está aprobado por defecto
        ]);

        // Asignación de roles utilizando Spatie
        $user->assignRole($request->role);

        // Creación del perfil correspondiente según el rol
        if ($request->role === 'client') {
            // Crear un cliente si el rol es 'client'
            $client = Client::create([
                'name'  => $request->name,
                'email' => $request->email,
            ]);

            // Relacionar el usuario con el cliente a través de la tabla pivot 'user_client'
            $user->clients()->attach($client->id);
        }

        if ($request->role === 'dog_walker') {
            // Crear un paseador si el rol es 'dog_walker'
            $walker = Walker::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'experience' => $request->experience, // Solo si el paseador tiene experiencia
            ]);

            // Relacionar el usuario con el paseador a través de la tabla pivot 'user_walker'
            $user->walkers()->attach($walker->id);
        }

        // Retornamos la respuesta
        return response()->json([
            'message' => 'Usuario registrado con éxito',
            'user'    => $user,
        ]);
    }
}
