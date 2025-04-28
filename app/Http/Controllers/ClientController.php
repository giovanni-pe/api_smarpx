<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller

{
    /**
     * @OA\Post(
     *     path="/api/clients",
     *     summary="Register a client",
     *     tags={"Clients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+51999999999"),
     *             @OA\Property(property="address", type="string", example="Lima, Peru")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Client created")
     * )
     */

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:clients',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client = Client::create($request->all());

        return response()->json(['client' => $client], 201);
    }
}
