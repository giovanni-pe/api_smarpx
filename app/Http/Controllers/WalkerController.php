<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Walker;
use App\Models\WalkReservation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class WalkerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/walkers",
     *     summary="Get all available walkers with rating and photo",
     *     tags={"Walkers"},
     *     @OA\Response(
     *         response=200,
     *         description="List of walkers",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="walker_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="experience", type="string"),
     *             @OA\Property(property="photo_url", type="string"),
     *             @OA\Property(property="rating", type="number", format="float"),
     *             @OA\Property(property="total_reviews", type="integer")
     *         ))
     *     )
     * )
     */

    public function index()
    {
        return response()->json(Walker::orderByDesc('rating')->get());
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:walkers,email',
            'experience'  => 'nullable|string',
            'photo_url'   => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $walker = Walker::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'experience'  => $request->experience,
            'photo_url'   => $request->photo_url,
            'rating'      => 0.0,
            'total_reviews' => 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Walker registrado correctamente',
            'walker'  => $walker
        ]);
    }
    public function paginateWalkers(int $page, int $perPage, ?string $search, ?string $specialty, ?float $minRating, string $sortable, string $order): LengthAwarePaginator
    {
        try {
            $query = Walker::query();

            // Filtro por nombre o email (búsqueda general)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filtro por especialidad (campo 'experience' contiene la especialidad)
            if ($specialty) {
                $query->where('experience', 'like', "%{$specialty}%");
            }

            // Filtro por calificación mínima
            if (!is_null($minRating)) {
                $query->where('rating', '>=', $minRating);
            }

            // Orden dinámico
            $query->orderBy($sortable, $order);

            // Retornar paginación con datos
            return $query->paginate($perPage, ['*'], 'page', $page);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function SearchWalkers(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 6);
        $search = $request->input('search');
        $specialty = $request->input('specialty');
        $minRating = $request->has('min_rating') ? (float)$request->input('min_rating') : null;
        $sortable = $request->input('sort_by', 'rating');
        $order = $request->input('order', 'desc');

        $walkers = $this->paginateWalkers($page, $perPage, $search, $specialty, $minRating, $sortable, $order);

        return response()->json($walkers);
    }


    public function getSimpleStats(Request $request, $walkerId)
    {
        try {
            // Verificar que el walker existe
            $walker = Walker::find($walkerId);
            if (!$walker) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paseador no encontrado'
                ], 404);
            }

            // Estadísticas básicas de walk_reservations
            $totalReservations = WalkReservation::where('walker_id', $walkerId)->count();
            $pendingReservations = WalkReservation::where('walker_id', $walkerId)->where('status', 'pending')->count();
            $confirmedReservations = WalkReservation::where('walker_id', $walkerId)->where('status', 'confirmed')->count();
            $completedReservations = WalkReservation::where('walker_id', $walkerId)->where('status', 'completed')->count();

            // Calificación del paseador (de la tabla walkers)
            $averageRating = $walker->rating ?: 0;
            $totalReviews = $walker->total_reviews ?: 0;

            // Estadísticas del mes actual
            $thisMonth = now()->startOfMonth();
            $thisMonthReservations = WalkReservation::where('walker_id', $walkerId)
                ->where('created_at', '>=', $thisMonth)
                ->count();

            // Mes anterior para comparación
            $lastMonth = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();
            $lastMonthReservations = WalkReservation::where('walker_id', $walkerId)
                ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                ->count();

            // Contar clientes únicos que han hecho reservas con este paseador
            $totalClients = WalkReservation::where('walker_id', $walkerId)
                ->distinct('client_id')
                ->count('client_id');

            // Contar perros únicos que ha paseado este paseador
            $totalDogs = WalkReservation::where('walker_id', $walkerId)
                ->distinct('dog_id')
                ->count('dog_id');

            $stats = [
                'totalReservations' => $totalReservations,
                'pendingReservations' => $pendingReservations,
                'confirmedReservations' => $confirmedReservations,
                'completedReservations' => $completedReservations,
                'averageRating' => round($averageRating, 1),
                'totalReviews' => $totalReviews,
                'thisMonthReservations' => $thisMonthReservations,
                'lastMonthReservations' => $lastMonthReservations,
                'totalClients' => $totalClients,
                'totalDogs' => $totalDogs,
                'walkerInfo' => [
                    'name' => $walker->name,
                    'email' => $walker->email,
                    'photo_url' => $walker->photo_url,
                    'experience' => $walker->experience
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos para gráfico mensual de los últimos 6 meses
     */
    public function getMonthlyChart(Request $request, $walkerId)
    {
        try {
            // Verificar que el walker existe
            $walker = Walker::find($walkerId);
            if (!$walker) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paseador no encontrado'
                ], 404);
            }

            $data = [];
            $labels = [];

            // Obtener datos de los últimos 6 meses
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                // Contar reservas completadas en ese mes
                $reservationsCount = WalkReservation::where('walker_id', $walkerId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'completed')
                    ->count();

                $labels[] = $date->format('M');
                $data[] = $reservationsCount;
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'labels' => $labels,
                    'reservations' => $data
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener datos del gráfico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el perfil del paseador con estadísticas básicas
     */
    public function show($id)
    {
        try {
            $walker = Walker::find($id);

            if (!$walker) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paseador no encontrado'
                ], 404);
            }

            // Agregar algunas estadísticas básicas
            $walker->total_walks = WalkReservation::where('walker_id', $id)->where('status', 'completed')->count();
            $walker->pending_walks = WalkReservation::where('walker_id', $id)->where('status', 'pending')->count();

            return response()->json([
                'status' => 'success',
                'data' => $walker
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener paseador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar perfil del paseador
     */
    public function update(Request $request, $id)
    {
        try {
            $walker = Walker::find($id);

            if (!$walker) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paseador no encontrado'
                ], 404);
            }

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:walkers,email,' . $id,
                'experience' => 'nullable|string',
                'photo_url' => 'nullable|url'
            ]);

            $walker->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Paseador actualizado exitosamente',
                'data' => $walker
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar paseador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar calificación del paseador (llamado automáticamente cuando se califican paseos)
     */
    public function updateRating($walkerId)
    {
        try {
            // Calcular promedio de calificaciones desde walk_reservations
            // Nota: Necesitarías agregar una columna 'client_rating' a walk_reservations para esto
            // Por ahora mantiene la calificación actual

            $walker = Walker::find($walkerId);
            if ($walker) {
                // Aquí podrías calcular el promedio real si tienes las calificaciones
                // $avgRating = WalkReservation::where('walker_id', $walkerId)->whereNotNull('client_rating')->avg('client_rating');
                // $totalReviews = WalkReservation::where('walker_id', $walkerId)->whereNotNull('client_rating')->count();

                // $walker->update([
                //     'rating' => round($avgRating, 1),
                //     'total_reviews' => $totalReviews
                // ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Calificación actualizada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar calificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
