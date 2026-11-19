<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\SpaceTypeController;
use App\Http\Controllers\Api\UserRoleController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Consulta de tipos de espacio y espacios (todos los roles autenticados)
    Route::get('/space-types', [SpaceTypeController::class, 'index']);
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/spaces/{space}', [SpaceController::class, 'show']);
    Route::get('/spaces/{space}/availability', [SpaceController::class, 'availability']);

    // Gestión de espacios y tipos (solo admin)
    Route::middleware('role:admin')->group(function () {
        Route::post('/space-types', [SpaceTypeController::class, 'store']);
        Route::put('/space-types/{spaceType}', [SpaceTypeController::class, 'update']);
        Route::delete('/space-types/{spaceType}', [SpaceTypeController::class, 'destroy']);

        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);

        Route::get('/users', [UserRoleController::class, 'users']);
        Route::post('/users/{user}/role', [UserRoleController::class, 'assignRole']);
        Route::get('/roles-permissions', [UserRoleController::class, 'rolesAndPermissions']);
    });

    // Reservas
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store'])->middleware('permission:reservations.create');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    // Aprobación, rechazo y finalización de reservas (staff o admin)
    Route::middleware('role:admin|staff')->group(function () {
        Route::patch('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
        Route::patch('/reservations/{reservation}/reject', [ReservationController::class, 'reject']);
        Route::patch('/reservations/{reservation}/complete', [ReservationController::class, 'complete']);
    });
});