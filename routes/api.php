<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});

Route::get('/status', function () {
    return response()->json(['status' => 'API is working']);
});

// Routes pour les comptes
Route::middleware('auth:api')->group(function () {
    Route::apiResource('comptes', \App\Http\Controllers\CompteController::class);
    Route::post('comptes/{compte}/restore', [\App\Http\Controllers\CompteController::class, 'restore']);
    Route::delete('comptes/{compte}/force-delete', [\App\Http\Controllers\CompteController::class, 'forceDelete']);
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('transactions', \App\Http\Controllers\TransactionController::class);
    Route::post('transactions/depot', [\App\Http\Controllers\TransactionController::class, 'depot']);
    Route::post('transactions/retrait', [\App\Http\Controllers\TransactionController::class, 'retrait']);
    Route::post('transactions/transfert', [\App\Http\Controllers\TransactionController::class, 'transfert']);
    Route::post('transactions/paiement', [\App\Http\Controllers\TransactionController::class, 'paiement']);
});
