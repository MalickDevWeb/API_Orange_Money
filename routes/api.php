<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('login/otp', [AuthController::class, 'verifyOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('logout/otp', [AuthController::class, 'sendLogoutOtp']);
    Route::post('logout', [AuthController::class, 'verifyLogoutOtp']);
    Route::get('user', [AuthController::class, 'user']);
});

Route::get('/status', function () {
    return response()->json(['status' => 'API is working']);
});

// Routes pour les comptes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('comptes/me', [\App\Http\Controllers\CompteController::class, 'me']);
    Route::get('comptes/solde', [\App\Http\Controllers\CompteController::class, 'solde']);
    Route::get('comptes/creer', [\App\Http\Controllers\CompteController::class, 'creer']);
    Route::get('comptes/activate/{numero_compte}', [\App\Http\Controllers\CompteController::class, 'activate']);
    Route::get('comptes/supprimer/{numero_compte}', [\App\Http\Controllers\CompteController::class, 'supprimerGet']);
    Route::post('comptes/supprimer', [\App\Http\Controllers\CompteController::class, 'supprimer']);
    Route::apiResource('comptes', \App\Http\Controllers\CompteController::class);
    Route::post('comptes/{compte}/restore', [\App\Http\Controllers\CompteController::class, 'restore']);
    Route::delete('comptes/{compte}/force-delete', [\App\Http\Controllers\CompteController::class, 'forceDelete']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('transactions', \App\Http\Controllers\TransactionController::class);
    Route::post('transactions/depot', [\App\Http\Controllers\TransactionController::class, 'depot']);
    Route::post('transactions/retrait', [\App\Http\Controllers\TransactionController::class, 'retrait']);
    Route::post('transactions/transfert', [\App\Http\Controllers\TransactionController::class, 'transfert']);
    Route::post('transactions/paiement', [\App\Http\Controllers\TransactionController::class, 'paiement']);
    Route::post('transactions/achat-virtuel', [\App\Http\Controllers\TransactionController::class, 'achatVirtuel']);
});

// Routes d'administration
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('users/pending', [AdminController::class, 'getPendingUsers']);
    Route::post('users/{id}/approve', [AdminController::class, 'approveUser']);
    Route::post('users/{id}/reject', [AdminController::class, 'rejectUser']);
    Route::get('balance-requests/pending', [AdminController::class, 'getPendingBalanceRequests']);
    Route::post('balance-requests/{id}/approve', [AdminController::class, 'approveBalanceRequest']);
    Route::post('balance-requests/{id}/reject', [AdminController::class, 'rejectBalanceRequest']);
    Route::post('deposit', [AdminController::class, 'deposit']);
});

// Routes fournisseurs
Route::middleware('auth:sanctum')->prefix('suppliers')->group(function () {
    Route::post('balance-request', [UserController::class, 'requestBalance']);
});
