<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use App\Models\Walker;

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
}
