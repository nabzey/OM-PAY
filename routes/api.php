<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TransactionController;
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
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// Routes protégées (nécessitent authentification Bearer)
Route::middleware('auth:api')->group(function () {
    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{reference}', [TransactionController::class, 'show']);
    Route::post('/paiements', [TransactionController::class, 'effectuerPaiement']);
    Route::post('/transferts', [TransactionController::class, 'effectuerTransfert']);
});

// Ancienne route (pour compatibilité)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
