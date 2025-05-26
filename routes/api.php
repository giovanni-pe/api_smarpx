<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DogController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-profile', [AuthController::class, 'registerProfile']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->get('/show-profile', [UserController::class, 'showProfile']);

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
    Route::post('/dogs', [DogController::class, 'store']);
    Route::get('/dogs', [DogController::class, 'index']);
    // Solo admin aprueba paseadores
    Route::middleware('role:admin')->put('/approve-walker/{id}', [AuthController::class, 'approveWalker']);
});

use App\Http\Controllers\ClientController;

use App\Http\Controllers\WalkReservationController;
use App\Http\Controllers\WalkerController;
use App\Http\Controllers\ContactMessageController;

Route::post('/clients', [ClientController::class, 'store']);
Route::post('/storeWhitoutUser', [DogController::class, 'storeWhitoutUser']);

Route::post('/reservations', [WalkReservationController::class, 'store']);
Route::get('/walkers', [WalkerController::class, 'index']);
Route::post('/contact', [ContactMessageController::class, 'store']);
Route::post('/walk-reservations/demo', [WalkReservationController::class, 'storeDemo']);


Route::post('/walkers', [WalkerController::class, 'store']);
Route::get('/walkers/search', [WalkerController::class, 'SearchWalkers']);
// Obtener reservaciones del paseador
Route::get('/walkers/{walkerId}/reservations', [WalkReservationController::class, 'getWalkerReservations']);

// Aceptar una reservación
Route::patch('/reservations/{reservationId}/accept', [WalkReservationController::class, 'acceptReservation']);

// Rechazar una reservación
Route::patch('/reservations/{reservationId}/reject', [WalkReservationController::class, 'rejectReservation']);

// Iniciar un paseo
Route::patch('/reservations/{reservationId}/start', [WalkReservationController::class, 'startWalk']);

// Completar un paseo
Route::patch('/reservations/{reservationId}/complete', [WalkReservationController::class, 'completeWalk']);

// OPCIONAL: Para uso con autenticación
Route::middleware('auth:api')->group(function () {
    // Obtener reservaciones del paseador autenticado
    Route::get('/my-walker-reservations', [WalkReservationController::class, 'getMyWalkerReservations']);
});


// Agregar estas rutas al archivo routes/api.php

// ========== RUTAS PARA CLIENTES ==========

// Obtener reservaciones del cliente
Route::get('/clients/{clientId}/reservations', [WalkReservationController::class, 'getClientReservations']);

// Cancelar una reservación (por parte del cliente)
Route::patch('/reservations/{reservationId}/cancel-client', [WalkReservationController::class, 'cancelClientReservation']);

// Calificar un paseo completado
Route::patch('/reservations/{reservationId}/rate', [WalkReservationController::class, 'rateWalk']);

// OPCIONAL: Para uso con autenticación
Route::middleware('auth:api')->group(function () {
    // Obtener reservaciones del cliente autenticado
    Route::get('/my-reservations', [WalkReservationController::class, 'getMyClientReservations']);
});

// Estadísticas simples del paseador
Route::get('/walkers/{walkerId}/simple-stats', [WalkerController::class, 'getSimpleStats']);
Route::get('/walkers/{walkerId}/monthly-chart', [WalkerController::class, 'getMonthlyChart']);
