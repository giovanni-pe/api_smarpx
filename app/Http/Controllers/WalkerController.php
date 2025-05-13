<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Walker;
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
}
