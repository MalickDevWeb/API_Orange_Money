<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BalanceRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\TryCatchTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\ResponseMessage;
use App\Enums\MessagesErreursRequests;
use App\Enums\UserType;
use App\Enums\{UserStatus, BalanceRequestStatus, TransactionType, TransactionStatus};
use App\Enums\T;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Endpoints pour les administrateurs"
 * )
 */
class AdminController extends Controller
{
    use ApiResponseTrait, TryCatchTrait;

    public function __construct()
    {
        $this->middleware(T::passport->value);
        $this->middleware(function ($request, $next) {
            if (Auth::user()->type !== UserType::ADMIN->value) {
                return $this->errorResponse(MessagesErreursRequests::UNAUTHORIZED_ACCESS->value, 403);
            }
            return $next($request);
        });
    }

    /**
     * @OA\Get(
     *     path="/admin/users/pending",
     *     operationId="getPendingUsers",
     *     tags={"Administration"},
     *     summary="Récupérer les utilisateurs en attente d'approbation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs en attente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé")
     * )
     */
    public function getPendingUsers()
    {
        return $this->tryCatch(function () {
            return User::whereIn('type', [UserType::COMMERCANT->value, UserType::FOURNISSEUR->value])
                      ->where('statut', UserStatus::EN_ATTENTE->value)
                      ->get();
        }, ResponseMessage::PENDING_USERS_RETRIEVED->value);
    }

    /**
     * @OA\Post(
     *     path="/admin/users/{id}/approve",
     *     operationId="approveUser",
     *     tags={"Administration"},
     *     summary="Approuver un utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur approuvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Utilisateur approuvé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function approveUser($id)
    {
        try {
            $id = trim($id, '"');
            $user = User::findOrFail($id);

            if (!($user->isCommercant() || $user->isFournisseur())) {
                return $this->errorResponse(MessagesErreursRequests::APPROVABLE_TYPE_ERROR->value, 400);
            }

            if ($user->statut === UserStatus::ACTIF->value) {
                return $this->successResponse(null, ResponseMessage::USER_ALREADY_APPROVED->value);
            }

            $user->update(['statut' => UserStatus::ACTIF->value]);

            return $this->successResponse(null, ResponseMessage::USER_APPROVED->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/users/{id}/reject",
     *     operationId="rejectUser",
     *     tags={"Administration"},
     *     summary="Rejeter un utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="motif_rejet", type="string", example="Documents insuffisants")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur rejeté",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Utilisateur rejeté")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function rejectUser(Request $request, $id)
    {
        try {
            $request->validate([
                MessagesErreursRequests::VALIDATION_MOTIF_REJET->value => MessagesErreursRequests::VALIDATION_MOTIF_REJET_RULES->value
            ]);

            $id = trim($id, '"');
            $user = User::findOrFail($id);

            if (!($user->isCommercant() || $user->isFournisseur())) {
                return $this->errorResponse(MessagesErreursRequests::REJECTABLE_TYPE_ERROR->value, 400);
            }

            $user->update(['statut' => UserStatus::INACTIF->value]);

            // TODO: Peut-être stocker le motif de rejet quelque part

            return $this->successResponse(null, ResponseMessage::USER_REJECTED->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/balance-requests/pending",
     *     operationId="getPendingBalanceRequests",
     *     tags={"Administration"},
     *     summary="Récupérer les demandes de solde en attente",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des demandes en attente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="supplier", ref="#/components/schemas/User"),
     *                 @OA\Property(property="montant", type="number"),
     *                 @OA\Property(property="statut", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé")
     * )
     */
    public function getPendingBalanceRequests()
    {
        return $this->tryCatch(function () {
            return BalanceRequest::with('supplier')
                               ->where('statut', BalanceRequestStatus::EN_ATTENTE->value)
                               ->get();
        }, ResponseMessage::PENDING_BALANCE_REQUESTS_RETRIEVED->value);
    }

    /**
     * @OA\Post(
     *     path="/admin/balance-requests/{id}/approve",
     *     operationId="approveBalanceRequest",
     *     tags={"Administration"},
     *     summary="Approuver une demande de solde",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande approuvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Demande de solde approuvée")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=404, description="Demande non trouvée")
     * )
     */
    public function approveBalanceRequest($id)
    {
        try {
            $id = trim($id, '"');
            $request = BalanceRequest::findOrFail($id);

            $request->update([
                'statut' => BalanceRequestStatus::APPROUVEE->value,
                'admin_id' => Auth::id(),
                'traitee_at' => now()
            ]);

            // Créer une transaction de dépôt pour ajouter le montant au solde du fournisseur
            $supplier = $request->supplier;
            if ($supplier && $supplier->comptes->count() > 0) {
                $compte = $supplier->comptes->first();
                \App\Models\Transaction::create([
                    'type' => TransactionType::DEPOT->value,
                    'montant' => $request->montant,
                    'reference' => 'DEP-APPROVAL-' . strtoupper(uniqid()),
                    'statut' => TransactionStatus::REUSSIE->value,
                    'note' => 'Approbation de demande de solde',
                    'compte_emetteur_id' => null,
                    'compte_recepteur_id' => $compte->id,
                    'date_transaction' => now(),
                ]);
            }

            return $this->successResponse(null, ResponseMessage::BALANCE_REQUEST_APPROVED->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/balance-requests/{id}/reject",
     *     operationId="rejectBalanceRequest",
     *     tags={"Administration"},
     *     summary="Rejeter une demande de solde",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="motif_rejet", type="string", example="Montant trop élevé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande rejetée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Demande de solde rejetée")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=404, description="Demande non trouvée")
     * )
     */
    public function rejectBalanceRequest(Request $request, $id)
    {
        try {
            $request->validate([
                MessagesErreursRequests::VALIDATION_MOTIF_REJET->value => MessagesErreursRequests::VALIDATION_MOTIF_REJET_RULES->value
            ]);

            $id = trim($id, '"');
            $balanceRequest = BalanceRequest::findOrFail($id);

            $balanceRequest->update([
                'statut' => BalanceRequestStatus::REJETEE->value,
                'motif_rejet' => $request->motif_rejet,
                'admin_id' => Auth::id(),
                'traitee_at' => now()
            ]);

            return $this->successResponse(null, ResponseMessage::BALANCE_REQUEST_REJECTED->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/deposit",
     *     operationId="adminDeposit",
     *     tags={"Administration"},
     *     summary="Effectuer un dépôt sur le compte d'un client par numéro de téléphone",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","montant"},
     *             @OA\Property(property="telephone", type="string", example="705334611"),
     *             @OA\Property(property="montant", type="number", format="float", example=50000),
     *             @OA\Property(property="note", type="string", example="Dépôt client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=404, description="Client non trouvé")
     * )
     */
    public function deposit(Request $request)
    {
        try {
            $data = $request->validate([
                MessagesErreursRequests::VALIDATION_TELEPHONE->value => MessagesErreursRequests::VALIDATION_TELEPHONE_RULES->value,
                MessagesErreursRequests::VALIDATION_MONTANT->value => 'required|numeric|min:0.01',
                MessagesErreursRequests::VALIDATION_NOTE->value => MessagesErreursRequests::VALIDATION_NOTE_RULES->value,
            ]);

            // Trouver le client par téléphone
            $client = User::where('telephone', $data['telephone'])
                          ->where('type', UserType::CLIENT->value)
                          ->first();

            if (!$client) {
                return $this->errorResponse('Client non trouvé', 404);
            }

            // Trouver le compte du client
            $compte = $client->comptes->first();
            if (!$compte) {
                return $this->errorResponse('Aucun compte trouvé pour ce client', 404);
            }

            // Créer la transaction de dépôt
            \App\Models\Transaction::create([
                'type' => TransactionType::DEPOT->value,
                'montant' => $data['montant'],
                'reference' => 'DEP-ADMIN-' . strtoupper(uniqid()),
                'statut' => TransactionStatus::REUSSIE->value,
                'note' => $data['note'] ?? 'Dépôt effectué par l\'admin',
                'compte_emetteur_id' => null,
                'compte_recepteur_id' => $compte->id,
                'date_transaction' => now(),
            ]);

            return $this->respondCreated(null, ResponseMessage::DEPOSIT_SUCCESS->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
