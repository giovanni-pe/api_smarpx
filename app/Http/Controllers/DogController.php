<?php

namespace App\Http\Controllers;

use App\Models\Dog;
use App\Models\Client;
use Illuminate\Http\Request;

class DogController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/dogs",
     *     summary="Register a dog and assign to client",
     *     tags={"Dogs"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "breed", "age", "energy_level", "client_id"},
     *                 @OA\Property(property="name", type="string", example="Toby"),
     *                 @OA\Property(property="breed", type="string", example="Labrador"),
     *                 @OA\Property(property="age", type="string", example="3 years"),
     *                 @OA\Property(property="size", type="string", example="Medium"),
     *                 @OA\Property(property="energy_level", type="string", enum={"low","medium","high"}),
     *                 @OA\Property(property="photo", type="file"),
     *                 @OA\Property(property="client_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Dog created and linked to client")
     * )
     */

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string',
            'breed'        => 'required|string',
            'age'          => 'required|string',
            'size'         => 'nullable|string',
            'energy_level' => 'required|in:low,medium,high',
            'photo'        => 'nullable|image|max:2048',
            'client_id'    => 'required|exists:clients,client_id',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('uploads/dogs', 'public');
            $photoUrl = '/storage/' . $photoPath;
        }

        $dog = Dog::create([
            'name'         => $request->name,
            'breed'        => $request->breed,
            'age'          => $request->age,
            'size'         => $request->size,
            'energy_level' => $request->energy_level,
            'photo_url'    => $photoUrl,
        ]);

        $dog->owners()->attach($request->client_id);

        return response()->json(['dog' => $dog], 201);
    }
}
