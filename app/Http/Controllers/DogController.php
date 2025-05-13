<?php

namespace App\Http\Controllers;

use App\Models\Dog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class DogController extends Controller
{

    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();

        // Obtener el cliente asociado al usuario autenticado
        $client = $user->clients()->first();

        // Verificamos si el usuario está asociado a un cliente
        if (!$client) {
            return response()->json(['error' => 'No se encontró un cliente asociado a este usuario'], 403);
        }

        // Filtros
        $query = $client->dogs();

        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('age') && $request->age != '') {
            $query->where('age', $request->age);
        }

        // Paginación
        $dogs = $query->paginate(10); // Cambia 10 por la cantidad que desees por página

        return response()->json($dogs);
    }


    public function storeWhitoutUser(Request $request)
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
    public function store(Request $request)
    {
        try {
            // Obtener el usuario autenticado
            $user = $request->user();

            // Obtener el cliente asociado al usuario autenticado
            $client = $user->clients()->first();

            // Verificamos si el usuario está asociado a un cliente
            if (!$client) {
                return response()->json(['error' => 'No se encontró un cliente asociado a este usuario'], 403);
            }

            // Validación de los datos del perro
            $request->validate([
                'name'         => 'required|string',
                'breed'        => 'required|string',
                'age'          => 'required|string',
                'size'         => 'nullable|string',
                'energy_level' => 'required|in:low,medium,high',
            ]);

            $photoUrl = null;
            if ($request->hasFile('photo')) {
                // Guardar la foto del perro
                $photoPath = $request->file('photo')->store('uploads/dogs', 'public');
                $photoUrl = '/storage/' . $photoPath;
            }

            // Crear el perro y asociarlo al cliente autenticado
            $dog = Dog::create([
                'name'         => $request->name,
                'breed'        => $request->breed,
                'age'          => $request->age,
                'size'         => $request->size,
                'energy_level' => $request->energy_level,
                'photo_url'    => $photoUrl,
            ]);

            // Asociar el perro al cliente mediante la tabla pivot
            $dog->clients()->attach($client->id);

            // Retornar respuesta exitosa con los datos del perro
            return response()->json(['dog' => $dog], 201);
        } catch (\Exception $e) {
            // Si ocurre un error, capturarlo y devolver una respuesta de error
            return response()->json([
                'error' => 'Hubo un error al registrar el perro: ' . $e->getMessage()
            ], 500);
        }
    }
}
