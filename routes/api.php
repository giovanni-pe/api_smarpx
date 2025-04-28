<?php 
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

    // Solo admin aprueba paseadores
    Route::middleware('role:admin')->put('/approve-walker/{id}', [AuthController::class, 'approveWalker']);
});

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DogController;
use App\Http\Controllers\WalkReservationController;
use App\Http\Controllers\WalkerController;
use App\Http\Controllers\ContactMessageController;

Route::post('/clients', [ClientController::class, 'store']);
Route::post('/dogs', [DogController::class, 'store']);
Route::post('/reservations', [WalkReservationController::class, 'store']);
Route::get('/walkers', [WalkerController::class, 'index']);
Route::post('/contact', [ContactMessageController::class, 'store']);
Route::post('/walk-reservations/demo', [WalkReservationController::class, 'storeDemo']);
