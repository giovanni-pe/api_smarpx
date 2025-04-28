<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageConfirmation;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
     /**
     * @OA\Post(
     *     path="/api/contact",
     *     summary="Submit contact message from the website",
     *     tags={"Contact"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "subject", "message"},
     *             @OA\Property(property="name", type="string", example="María"),
     *             @OA\Property(property="email", type="string", example="maria@example.com"),
     *             @OA\Property(property="subject", type="string", example="Pregunta sobre paseos"),
     *             @OA\Property(property="message", type="string", example="¿Cuánto cuesta un paseo individual?")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Message received")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email',
            'subject' => 'required|string|max:150',
            'message' => 'required|string',
        ]);

        ContactMessage::create($validated);



        // Enviar confirmación al cliente
        Mail::to($validated['email'])->send(new ContactMessageConfirmation($validated));

        return response()->json([
            'status' => 'success',
            'message' => '¡Gracias por tu mensaje, ' . $validated['name'] . '! Te hemos enviado una confirmación a ' . $validated['email'] . '.'
        ]);
    }
}
