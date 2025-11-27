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
Route::post('sendOTP', [AuthController::class, 'login'])->name('sendOTP');
Route::post('login/otp', [AuthController::class, 'verifyOtp']);
Route::post('send-otp', [AuthController::class, 'sendOtp']);
Route::post('verify-otp-email', [AuthController::class, 'verifyOtpEmail']);

Route::middleware(T::passport->value)->group(function () {
    Route::get('logout/otp', [AuthController::class, 'sendLogoutOtp'])->name('sendOtp');
    Route::post('logout', [AuthController::class, 'verifyLogoutOtp'])->name('verifyOtp');
    Route::get('user', [AuthController::class, 'user']);
    Route::get('user/details', [UserController::class, 'detailsUser']);
    Route::put('user', [UserController::class, 'updateProfile']);
});

Route::get('/status', function () {
    return response()->json(['status' => 'API is working']);
});

// Routes pour les comptes
Route::middleware(T::passport->value)->group(function () {
    // Création de compte
    Route::post('compte/nouveaucompte', [\App\Http\Controllers\CompteController::class, 'nouveaucompte']);

    // Récupération des comptes
    Route::get('comptes/mesComptes', [\App\Http\Controllers\CompteController::class, 'mesComptes']);
    Route::get('comptes/{numeroCompte}', [\App\Http\Controllers\CompteController::class, 'showByNumero']);

    // Soldes
    Route::get('compte/solde', [\App\Http\Controllers\CompteController::class, 'solde']);
    Route::get('compte/{numeroCompte}/solde', [\App\Http\Controllers\CompteController::class, 'soldeByNumero']);

    // Modification de compte
    Route::put('compte/{numeroCompte}/modifier', [\App\Http\Controllers\CompteController::class, 'modifier']);

    // Switch de compte actif
    Route::post('compte/{numeroCompte}/switch', [\App\Http\Controllers\CompteController::class, 'switch']);

    // Suppression de compte
    Route::delete('compte/{numeroCompte}/supprimer', [\App\Http\Controllers\CompteController::class, 'supprimer']);
    Route::post('otp/confirmation', [\App\Http\Controllers\CompteController::class, 'confirmationOtp']);
    Route::delete('compte/{numeroCompte}/force-delete', [\App\Http\Controllers\CompteController::class, 'forceDelete']);

    // Restauration de compte
    Route::post('compte/{numeroCompte}/restaurer', [\App\Http\Controllers\CompteController::class, 'restaurer']);

    // Routes existantes (pour compatibilité)
    Route::get('comptes/me', [\App\Http\Controllers\CompteController::class, 'me']);
    Route::get('balance', [\App\Http\Controllers\CompteController::class, 'solde']); // Endpoint commun pour le solde
    Route::post('comptes/activate/{nom_compte}', [\App\Http\Controllers\CompteController::class, 'activate']);
    Route::apiResource('comptes', \App\Http\Controllers\CompteController::class);
    Route::post('comptes/{compte}/restore', [\App\Http\Controllers\CompteController::class, 'restore']);
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
    Route::post('users/{telephone}/action', [AdminController::class, 'userAction']);
     Route::post('balance-requests/{telephone}/action', [AdminController::class, 'balanceRequestAction']);
    Route::put('users/{user}/rights', [AdminController::class, 'updateUserRights']);
    Route::put('users/{user}/tax', [AdminController::class, 'setUserTax']);
    Route::post('users/{user}/comptes', [AdminController::class, 'createCompteForUser']);
    Route::get('comptes', [AdminController::class, 'getAllComptes']);
    Route::get('statistics/daily', [AdminController::class, 'getDailyStatistics']);
    Route::put('fees/global', [AdminController::class, 'updateGlobalFees']);
    Route::get('balance-requests/pending', [AdminController::class, 'getPendingBalanceRequests']);
    Route::get('actions', [AdminController::class, 'getAdminActions']);

});

// Routes fournisseurs
Route::middleware(T::passport->value)->prefix('suppliers')->group(function () {});
