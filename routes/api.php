<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/comptes', [CompteController::class, 'store']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    // Dashboard avec solde intégré
    Route::get('/dashboard', [AuthController::class, 'dashboard']);

    // Comptes
    Route::get('/compte/solde', [CompteController::class, 'solde']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'getTransactionsByCompte']);
    Route::post('/transactions/paiement', [TransactionController::class, 'effectuerPaiement']);
    Route::post('/transactions/transfert', [TransactionController::class, 'effectuerTransfert']);

    // Historique détaillé (optionnel)
    Route::get('/transactions/{reference}', [TransactionController::class, 'show']);
});

Route::get('/qr-code/{codeMarchand}', [TransactionController::class, 'genererQrCode']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->get('/dashboard', [AuthController::class, 'dashboard']);
