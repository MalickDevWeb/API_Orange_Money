<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Enums\T;

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
Route::post('send-otp', [AuthController::class, 'sendOtp']);
Route::post('verify-otp-email', [AuthController::class, 'verifyOtpEmail']);

Route::middleware(T::passport->value)->group(function () {
    Route::get('logout/otp', [AuthController::class, 'sendLogoutOtp']);
    Route::post('logout', [AuthController::class, 'verifyLogoutOtp']);
    Route::get('user', [AuthController::class, 'user']);
    Route::put('user', [UserController::class, 'updateProfile']);
});

Route::get('/status', function () {
    return response()->json(['status' => 'API is working']);
});

// Routes pour les comptes
Route::middleware(T::passport->value)->group(function () {
    Route::get('comptes/me', [\App\Http\Controllers\CompteController::class, 'me']);
    Route::get('balance', [\App\Http\Controllers\CompteController::class, 'solde']); // Endpoint commun pour le solde
    Route::post('comptes/activate/{nom_compte}', [\App\Http\Controllers\CompteController::class, 'activate']);
    Route::apiResource('comptes', \App\Http\Controllers\CompteController::class);
    Route::post('comptes/{compte}/restore', [\App\Http\Controllers\CompteController::class, 'restore']);
    Route::delete('comptes/{compte}/force-delete', [\App\Http\Controllers\CompteController::class, 'forceDelete']);
});

Route::middleware(T::passport->value)->group(function () {
    Route::apiResource('transactions', \App\Http\Controllers\TransactionController::class)->except(['update', 'destroy']);
    Route::post('transactions/retrait', [\App\Http\Controllers\TransactionController::class, 'retrait']);
    Route::post('transactions/confirm-retrait', [\App\Http\Controllers\TransactionController::class, 'confirmRetrait']);
    Route::post('transactions/achat-virtuel', [\App\Http\Controllers\TransactionController::class, 'achatVirtuel']);
    Route::post('transactions/demande', [\App\Http\Controllers\TransactionController::class, 'balanceRequest']);
    Route::post('transactions/unified', [\App\Http\Controllers\TransactionController::class, 'unifiedTransaction']);
});

// Routes d'administration
Route::middleware(T::passport->value)->prefix('admin')->group(function () {
    Route::get('users/pending', [AdminController::class, 'getPendingUsers']);
    Route::post('users/{telephone}/approve', [AdminController::class, 'approveUser']);
    Route::post('users/{telephone}/reject', [AdminController::class, 'rejectUser']);
    Route::put('users/{user}/rights', [AdminController::class, 'updateUserRights']);
    Route::post('users/{user}/ban', [AdminController::class, 'banUser']);
    Route::post('users/{user}/unban', [AdminController::class, 'unbanUser']);
    Route::put('users/{user}/tax', [AdminController::class, 'setUserTax']);
    Route::post('users/{user}/comptes', [AdminController::class, 'createCompteForUser']);
    Route::get('comptes', [AdminController::class, 'getAllComptes']);
    Route::get('statistics/daily', [AdminController::class, 'getDailyStatistics']);
    Route::put('fees/global', [AdminController::class, 'updateGlobalFees']);
    Route::get('balance-requests/pending', [AdminController::class, 'getPendingBalanceRequests']);
    Route::post('balance-requests/{telephone}/approve', [AdminController::class, 'approveBalanceRequest']);
    Route::post('balance-requests/{telephone}/reject', [AdminController::class, 'rejectBalanceRequest']);
    Route::post('deposit', [AdminController::class, 'deposit']);
});

// Routes fournisseurs
Route::middleware(T::passport->value)->prefix('suppliers')->group(function () {
    // Balance request moved to transactions section
});
