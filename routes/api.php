<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques (pas besoin d'authentification)
Route::post('/comptes', [CompteController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/oauth/token', function () {
    // Route pour Passport OAuth2
    return response()->json(['message' => 'Use /api/login for authentication']);
});

// Routes protégées (nécessitent authentification OAuth2)
Route::middleware('auth:api')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/client', [AuthController::class, 'client']);

    // Gestion des comptes (sauf création qui est publique)
    Route::get('/comptes', [CompteController::class, 'index']);
    Route::get('/comptes/{compte}', [CompteController::class, 'show']);
    Route::put('/comptes/{compte}', [CompteController::class, 'update']);
    Route::delete('/comptes/{compte}', [CompteController::class, 'destroy']);
});

// Ancienne route (pour compatibilité)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
