<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Dog;
use App\Models\WalkReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WalkReservationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/reservations",
     *     summary="Create a walk reservation",
     *     tags={"Walk Reservations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "dog_id", "reservation_date", "reservation_time"},
     *             @OA\Property(property="client_id", type="integer", example=1),
     *             @OA\Property(property="dog_id", type="integer", example=2),
     *             @OA\Property(property="walker_id", type="integer", example=3),
     *             @OA\Property(property="reservation_date", type="string", format="date", example="2025-05-01"),
     *             @OA\Property(property="reservation_time", type="string", example="14:00")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Reservation created")
     * )
     */

    public function store(Request $request)
    {
        $request->validate([
            'client_id'         => 'required|exists:clients,client_id',
            'dog_id'            => 'required|exists:dogs,dog_id',
            'reservation_date'  => 'required|date',
            'reservation_time'  => 'required',
            'walker_id'         => 'nullable|exists:walkers,walker_id'
        ]);

        $reservation = WalkReservation::create($request->all());

        return response()->json(['reservation' => $reservation], 201);
    }
    /**
     * @OA\Post(
     *     path="/api/walk-reservations/demo",
     *     summary="Crear una reserva demo con nuevo cliente y perro",
     *     tags={"Walk Reservations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "dog_name", "dog_breed", "dog_age", "dog_energy"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="phone", type="string", example="+51 999 999 999"),
     *             @OA\Property(property="dog_name", type="string", example="Toby"),
     *             @OA\Property(property="dog_breed", type="string", example="Labrador"),
     *             @OA\Property(property="dog_age", type="string", example="3 años"),
     *             @OA\Property(property="dog_energy", type="string", enum={"low", "medium", "high"}, example="medium")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva demo creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="¡Reserva demo creada con éxito! Pronto nos pondremos en contacto contigo.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor"
     *     )
     * )
     */

     public function storeDemo(Request $request)
     {
         $validated = $request->validate([
             'name'        => 'required|string|max:100',
             'email'       => 'required|email',
             'phone'       => 'nullable|string|max:20',
             'dog_name'    => 'required|string|max:100',
             'dog_breed'   => 'required|string|max:100',
             'dog_age'     => 'required|string|max:30',
             'dog_energy'  => 'required|in:low,medium,high'
         ]);
     
         DB::beginTransaction();
     
         try {
    
             $client = Client::where('email', $validated['email'])->first();
     
             if (!$client) {
 
                 $client = Client::create([
                     'name' => $validated['name'],
                     'email' => $validated['email'],
                     'phone' => $validated['phone'],
                     'address' => null
                 ]);
             }
          

             $dog = Dog::create([
                 'name' => $validated['dog_name'],
                 'breed' => $validated['dog_breed'],
                 'age' => $validated['dog_age'],
                 'size' => 'medium',
                 'energy_level' => $validated['dog_energy']
             ]);
     
             $client->dogs()->attach($dog->id);
     
             WalkReservation::create([
                 'client_id' => $client->id,
                 'dog_id' => $dog->id,
                 'reservation_date' => now()->addDay()->toDateString(),
                 'reservation_time' => '10:00:00',
                 'status' => 'pending',
                 'walker_id' => null
             ]);
     
             DB::commit();
     
             return response()->json([
                 'status' => 'success',
                 'message' => '¡Reserva demo creada con éxito! Pronto nos pondremos en contacto contigo.'
             ]);
         } catch (\Throwable $e) {
             DB::rollBack();
     
             return response()->json([
                 'status' => 'error',
                 'message' => 'Error al registrar la reserva demo.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
     
     
}
