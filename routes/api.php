<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API
|--------------------------------------------------------------------------
| Rutas PÚBLICAS (visitante): catálogo de eventos, detalle, categorías, reseñas.
| Rutas PROTEGIDAS (auth:sanctum): perfil, organizador, eventos propios,
| inscripciones, check-in y reseñas.
*/

// ---------------- AUTENTICACIÓN ----------------
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// ---------------- PÚBLICO (visitante) ----------------
Route::get('categories', [CategoryController::class, 'index']);
Route::get('events', [EventController::class, 'index']);          // catálogo + filtros
Route::get('events/{id}', [EventController::class, 'show']);      // detalle
Route::get('events/{eventId}/reviews', [ReviewController::class, 'index']); // reseñas públicas

// ---------------- PROTEGIDO (auth:sanctum) ----------------
Route::middleware('auth:sanctum')->group(function () {

    // Sesión y perfil
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('me/organizer', [AuthController::class, 'convertirseEnOrganizador']);

    // Gestión de eventos (organizador dueño, controlado por EventPolicy)
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{id}', [EventController::class, 'update']);
    Route::delete('events/{id}', [EventController::class, 'destroy']);
    Route::post('events/{id}/cancel', [EventController::class, 'cancel']);
    Route::post('events/{id}/cover', [EventController::class, 'updateCover']);
    Route::get('events/{eventId}/attendees', [RegistrationController::class, 'inscritos']);
    Route::post('events/{eventId}/check-in', [RegistrationController::class, 'checkIn']);

    // Inscripciones del asistente
    Route::get('my-tickets', [RegistrationController::class, 'misTickets']);
    Route::post('events/{eventId}/register', [RegistrationController::class, 'store']);
    Route::delete('events/{eventId}/register', [RegistrationController::class, 'destroy']);

    // Reseñas
    Route::post('events/{eventId}/reviews', [ReviewController::class, 'store']);
});
