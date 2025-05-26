<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Dog;
use App\Models\WalkReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalkReservationController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'         => 'required|exists:clients,id',
            'dog_id'            => 'required|exists:dogs,id',
            'reservation_date'  => 'required|date',
            'reservation_time'  => 'required',
            'walker_id'         => 'nullable|exists:walkers,id'
        ]);

        $validated['status'] = 'pending'; // Aquí estableces el valor por defecto

        $reservation = WalkReservation::create($validated);

        return response()->json(['reservation' => $reservation], 201);
    }


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
    public function getWalkerReservations(Request $request, $walkerId)
    {
        try {
            $request->validate([
                'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'page' => 'nullable|integer|min:1'
            ]);

            $query = WalkReservation::with(['client', 'dog'])
                ->where('walker_id', $walkerId);

            // Filtros opcionales
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->where('reservation_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('reservation_date', '<=', $request->date_to);
            }

            $query->orderBy('reservation_date', 'desc')
                ->orderBy('reservation_time', 'desc');

            $perPage = $request->get('per_page', 10);
            $reservations = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $reservations->items(),
                'pagination' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las reservaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function acceptReservation(Request $request, $reservationId)
    {
        try {
            $reservation = WalkReservation::with(['client', 'dog', 'walker'])->findOrFail($reservationId);

            // Verificar que la reservación esté en estado 'pending'
            if ($reservation->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta reservación ya no puede ser aceptada. Estado actual: ' . $reservation->status
                ], 400);
            }

            // Verificar que la fecha no haya pasado
            $reservationDateTime = \Carbon\Carbon::parse($reservation->reservation_date . ' ' . $reservation->reservation_time);
            if ($reservationDateTime->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede aceptar una reservación que ya pasó'
                ], 400);
            }

            // Actualizar estado a 'confirmed'
            $reservation->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);

            // Recargar la reservación con las relaciones
            $reservation->load(['client', 'dog', 'walker']);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservación aceptada exitosamente',
                'reservation' => $reservation
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al aceptar la reservación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function rejectReservation(Request $request, $reservationId)
    {
        try {
            $request->validate([
                'rejection_reason' => 'nullable|string|max:500'
            ]);

            $reservation = WalkReservation::with(['client', 'dog'])->findOrFail($reservationId);

            // Verificar que la reservación esté en estado 'pending'
            if ($reservation->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta reservación ya no puede ser rechazada. Estado actual: ' . $reservation->status
                ], 400);
            }

            // Actualizar estado a 'cancelled'
            $reservation->update([
                'status' => 'cancelled',
                'rejection_reason' => $request->rejection_reason,
                'cancelled_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservación rechazada exitosamente',
                'reservation' => $reservation
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al rechazar la reservación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function startWalk(Request $request, $reservationId)
    {
        try {
            $reservation = WalkReservation::findOrFail($reservationId);

            if ($reservation->status !== 'confirmed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Solo se pueden iniciar paseos confirmados'
                ], 400);
            }

            $reservation->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Paseo iniciado exitosamente',
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al iniciar el paseo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function completeWalk(Request $request, $reservationId)
    {
        try {
            $request->validate([
                'completion_notes' => 'nullable|string|max:1000',
                'duration_minutes' => 'nullable|integer|min:1'
            ]);

            $reservation = WalkReservation::findOrFail($reservationId);

            if ($reservation->status !== 'in_progress') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Solo se pueden completar paseos en progreso'
                ], 400);
            }

            $reservation->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completion_notes' => $request->completion_notes,
                'duration_minutes' => $request->duration_minutes
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Paseo completado exitosamente',
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al completar el paseo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getClientReservations(Request $request, $clientId)
    {
        try {
            $request->validate([
                'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'page' => 'nullable|integer|min:1'
            ]);

            $query = WalkReservation::with(['walker', 'dog'])
                ->where('client_id', $clientId);

            // Filtros opcionales
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->where('reservation_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('reservation_date', '<=', $request->date_to);
            }

            // Ordenar por fecha más reciente
            $query->orderBy('reservation_date', 'desc')
                ->orderBy('reservation_time', 'desc');

            $perPage = $request->get('per_page', 10);
            $reservations = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $reservations->items(),
                'pagination' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las reservaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una reservación (por parte del cliente)
     */
    public function cancelClientReservation(Request $request, $reservationId)
    {
        try {
            $request->validate([
                'cancellation_reason' => 'nullable|string|max:500'
            ]);

            $reservation = WalkReservation::with(['walker', 'dog'])->findOrFail($reservationId);

            // Verificar que la reservación pertenece al cliente
            // En un entorno real, deberías verificar el client_id con el usuario autenticado

            // Solo se pueden cancelar reservaciones pendientes o confirmadas
            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta reservación no puede ser cancelada. Estado actual: ' . $reservation->status
                ], 400);
            }

            // Verificar que la reservación no haya empezado (al menos 2 horas antes)
            $reservationDateTime = \Carbon\Carbon::parse($reservation->reservation_date . ' ' . $reservation->reservation_time);
            $minimumCancelTime = now()->addHours(2);

            if ($reservationDateTime->lessThan($minimumCancelTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Las reservaciones solo pueden cancelarse con al menos 2 horas de anticipación'
                ], 400);
            }

            // Actualizar estado a 'cancelled'
            $reservation->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now(),
                'cancelled_by' => 'client'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservación cancelada exitosamente',
                'reservation' => $reservation
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cancelar la reservación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calificar un paseo completado
     */
    public function rateWalk(Request $request, $reservationId)
    {
        try {
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000'
            ]);

            $reservation = WalkReservation::with(['walker'])->findOrFail($reservationId);

            // Solo se pueden calificar paseos completados
            if ($reservation->status !== 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Solo se pueden calificar paseos completados'
                ], 400);
            }

            // Verificar que no haya sido calificado antes
            if ($reservation->client_rating) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta reservación ya ha sido calificada'
                ], 400);
            }

            // Actualizar la calificación
            $reservation->update([
                'client_rating' => $request->rating,
                'client_review' => $request->review,
                'rated_at' => now()
            ]);

            // Actualizar promedio del paseador (opcional)
            $this->updateWalkerRating($reservation->walker_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Calificación guardada exitosamente',
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al calificar el paseo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de reservaciones del cliente autenticado
     */
    public function getMyClientReservations(Request $request)
    {
        try {
            $clientId = Auth::user()->client_id ?? 1; // Temporal

            return $this->getClientReservations($request, $clientId);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las reservaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar promedio de calificación del paseador
     */
    private function updateWalkerRating($walkerId)
    {
        try {
            $averageRating = WalkReservation::where('walker_id', $walkerId)
                ->whereNotNull('client_rating')
                ->avg('client_rating');

            $totalReviews = WalkReservation::where('walker_id', $walkerId)
                ->whereNotNull('client_rating')
                ->count();

            // Actualizar en la tabla de walkers si existe
            DB::table('walkers')
                ->where('id', $walkerId)
                ->update([
                    'rating' => round($averageRating, 2),
                    'total_reviews' => $totalReviews,
                    'updated_at' => now()
                ]);
        } catch (\Exception $e) {
            Log::error('Error updating walker rating: ' . $e->getMessage());
        }
    }
}
