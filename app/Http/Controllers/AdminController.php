<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BalanceRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\TryCatchTrait;
use App\Traits\PaginatedSortedTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\ResponseMessage;
use App\Enums\MessagesErreursRequests;
use App\Enums\UserType;
use App\Enums\{UserStatus, BalanceRequestStatus, TransactionType, TransactionStatus};
use App\Enums\T;
use App\Services\AdminService;
use App\Interfaces\Services\CompteServiceInterface;
use App\Http\Requests\UpdateUserRightsRequest;
use App\Http\Requests\UpdateGlobalFeesRequest;
use App\Http\Requests\SetUserTaxRequest;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Endpoints pour les administrateurs - Gestion des droits utilisateurs, statistiques et configuration"
 * )
 *
 * @OA\Schema(
 *     schema="UserRights",
 *     type="object",
 *     @OA\Property(property="transfer_enabled", type="boolean", example=true, description="Autorisation générale de transfert"),
 *     @OA\Property(property="can_transfer_to_client", type="boolean", example=true, description="Autorisation de transfert vers clients"),
 *     @OA\Property(property="can_pay_merchant", type="boolean", example=true, description="Autorisation de paiement vers marchands")
 * )
 *
 * @OA\Schema(
 *     schema="GlobalFees",
 *     type="object",
 *     @OA\Property(property="transaction_fee", type="number", format="float", example=100.50, description="Frais de transaction globaux"),
 *     @OA\Property(property="merchant_percentage", type="number", format="float", example=5.25, description="Pourcentage appliqué aux marchands")
 * )
 *
 * @OA\Schema(
 *     schema="DailyStatistics",
 *     type="object",
 *     @OA\Property(property="transfers", type="integer", example=25, description="Nombre de transferts réussis"),
 *     @OA\Property(property="deposits", type="integer", example=10, description="Nombre de dépôts réussis"),
 *     @OA\Property(property="withdrawals", type="integer", example=8, description="Nombre de retraits réussis"),
 *     @OA\Property(property="merchant_payments", type="integer", example=15, description="Nombre de paiements marchands réussis")
 * )
 */
class AdminController extends Controller
{
    use ApiResponseTrait, TryCatchTrait, PaginatedSortedTrait;

    protected AdminService $adminService;
    protected CompteServiceInterface $compteService;

    public function __construct(AdminService $adminService, CompteServiceInterface $compteService)
    {
        $this->adminService = $adminService;
        $this->compteService = $compteService;
        $this->middleware(T::passport->value);
        $this->middleware(function ($request, $next) {
            if (Auth::user()->type !== UserType::ADMIN->value) {
                return $this->errorResponse(MessagesErreursRequests::UNAUTHORIZED_ACCESS->value, 403);
            }
            return $next($request);
        });
    }

    protected function getAllowedSortFields()
    {
        return ['created_at', 'updated_at', 'numero_compte', 'statut'];
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
     *     path="/admin/users/{telephone}/approve",
     *     operationId="approveUser",
     *     tags={"Administration"},
     *     summary="Approuver un utilisateur par numéro de téléphone",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="770000040"),
     *         description="Numéro de téléphone de l'utilisateur à approuver"
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
    public function approveUser($telephone)
    {
        try {
            $telephone = trim($telephone, '"');
            $user = User::where('telephone', $telephone)->firstOrFail();

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
     *     path="/admin/users/{telephone}/reject",
     *     operationId="rejectUser",
     *     tags={"Administration"},
     *     summary="Rejeter un utilisateur par numéro de téléphone",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="770000040"),
     *         description="Numéro de téléphone de l'utilisateur à rejeter"
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
    public function rejectUser(Request $request, $telephone)
    {
        try {
            $request->validate([
                MessagesErreursRequests::VALIDATION_MOTIF_REJET->value => MessagesErreursRequests::VALIDATION_MOTIF_REJET_RULES->value
            ]);

            $telephone = trim($telephone, '"');
            $user = User::where('telephone', $telephone)->firstOrFail();

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

    /**
     * @OA\Put(
     *     path="/api/admin/users/{user}/rights",
     *     operationId="updateUserRights",
     *     tags={"Administration"},
     *     summary="Modifier les droits de transfert d'un utilisateur",
     *     description="Permet à l'administrateur de définir les autorisations de transfert pour un utilisateur spécifique. Toutes les modifications sont automatiquement appliquées lors des futures transactions.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserRights")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Droits mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Droits de transfert mis à jour")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function updateUserRights(UpdateUserRightsRequest $request, User $user)
    {
        try {
            $rights = $request->only(['transfer_enabled', 'can_transfer_to_client', 'can_pay_merchant']);
            $success = $this->adminService->updateUserTransferRights($user->id, $rights);
            if (!$success) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }
            $this->adminService->logAdminAction(Auth::id(), 'update_user_rights', $user->id, $rights);
            return $this->successResponse(null, 'Droits de transfert mis à jour');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{user}/ban",
     *     operationId="banUser",
     *     tags={"Administration"},
     *     summary="Bannir un utilisateur",
     *     description="Bloque définitivement l'accès aux transactions pour cet utilisateur. Toutes les tentatives de transaction seront rejetées.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur à bannir",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur banni avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Utilisateur banni")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function banUser(User $user)
    {
        try {
            $success = $this->adminService->banUser($user->id);
            if (!$success) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }
            $this->adminService->logAdminAction(Auth::id(), 'ban_user', $user->id);
            return $this->successResponse(null, 'Utilisateur banni');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{user}/unban",
     *     operationId="unbanUser",
     *     tags={"Administration"},
     *     summary="Débannir un utilisateur",
     *     description="Restaure l'accès aux transactions pour un utilisateur précédemment banni.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur à débannir",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur débanni avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Utilisateur débanni")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function unbanUser(User $user)
    {
        try {
            $success = $this->adminService->unbanUser($user->id);
            if (!$success) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }
            $this->adminService->logAdminAction(Auth::id(), 'unban_user', $user->id);
            return $this->successResponse(null, 'Utilisateur débanni');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/statistics/daily",
     *     operationId="getDailyStatistics",
     *     tags={"Administration"},
     *     summary="Obtenir les statistiques journalières des transactions",
     *     description="Retourne le nombre de transactions réussies par type pour la journée en cours.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/DailyStatistics")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs")
     * )
     */
    public function getDailyStatistics()
    {
        try {
            $stats = $this->adminService->getDailyStatistics();
            return $this->successResponse($stats, 'Statistiques journalières');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/fees/global",
     *     operationId="updateGlobalFees",
     *     tags={"Administration"},
     *     summary="Mettre à jour les frais et pourcentages globaux",
     *     description="Configure les frais de transaction globaux et le pourcentage appliqué aux paiements marchands. Ces paramètres affectent automatiquement toutes les futures transactions.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/GlobalFees")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Frais globaux mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Frais globaux mis à jour")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function updateGlobalFees(UpdateGlobalFeesRequest $request)
    {
        try {
            $success = $this->adminService->updateGlobalFees(
                $request->transaction_fee,
                $request->merchant_percentage
            );
            if (!$success) {
                return $this->errorResponse('Erreur lors de la mise à jour', 500);
            }
            $this->adminService->logAdminAction(Auth::id(), 'update_global_fees', null, $request->all());
            return $this->successResponse(null, 'Frais globaux mis à jour');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{user}/tax",
     *     operationId="setUserTax",
     *     tags={"Administration"},
     *     summary="Définir la taxe spécifique d'un utilisateur",
     *     description="Applique une taxe personnalisée à un utilisateur. Cette taxe sera automatiquement ajoutée à tous ses paiements marchands en plus des frais globaux.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="tax_percentage", type="number", format="float", minimum=0, maximum=100, example=2.5, description="Pourcentage de taxe à appliquer (0-100%)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Taxe utilisateur définie avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Taxe utilisateur définie")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=422, description="Pourcentage invalide")
     * )
     */
    public function setUserTax(SetUserTaxRequest $request, User $user)
    {
        try {
            $success = $this->adminService->setUserTax($user->id, $request->tax_percentage);
            if (!$success) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }
            $this->adminService->logAdminAction(Auth::id(), 'set_user_tax', $user->id, ['tax_percentage' => $request->tax_percentage]);
            return $this->successResponse(null, 'Taxe utilisateur définie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/comptes",
     *     operationId="getAllComptes",
     *     tags={"Administration"},
     *     summary="Lister tous les comptes avec filtrage avancé (Admin uniquement)",
     *     description="Liste paginée de tous les comptes avec possibilité de filtrer par statut, solde, numéro de compte, titulaire et recherche globale. Tri possible par tous les champs principaux.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif","inactif","suspendu"})
     *     ),
     *     @OA\Parameter(
     *         name="solde_min",
     *         in="query",
     *         description="Solde minimum",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="solde_max",
     *         in="query",
     *         description="Solde maximum",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="query",
     *         description="Rechercher par numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="titulaire",
     *         in="query",
     *         description="Rechercher par titulaire",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche globale dans numéro, titulaire et code marchand",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at","updated_at","numero_compte","solde","statut"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Direction du tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc","desc"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page (1-100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec filtrage et pagination",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                         @OA\Property(property="titulaire", type="string", example="John Doe"),
     *                         @OA\Property(property="solde", type="number", format="float", example=15000.5),
     *                         @OA\Property(property="statut", type="string", example="actif"),
     *                         @OA\Property(property="code_marchand", type="string", nullable=true, example="MRC001")
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - Réservé aux administrateurs")
     * )
     */
    /**
     * @OA\Post(
     *     path="/admin/users/{user}/comptes",
     *     operationId="createCompteForUser",
     *     tags={"Administration"},
     *     summary="Créer un compte pour un utilisateur spécifique (Admin uniquement)",
     *     description="Permet à l'administrateur de créer un compte secondaire pour un utilisateur existant.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_compte"},
     *             @OA\Property(property="nom_compte", type="string", example="compteprive", description="Nom unique du compte secondaire")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=400, description="Nom de compte déjà utilisé ou réservé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function createCompteForUser(Request $request, User $user)
    {
        try {
            // Validation pour comptes secondaires : seulement nom_compte requis
            $request->validate([
                'nom_compte' => 'required|string',
            ]);

            $data = $request->only(['nom_compte']);

            if ($data['nom_compte'] === 'compte principal') {
                return $this->errorResponse('Le nom "compte principal" est réservé au premier compte créé automatiquement lors de l\'inscription.', 400);
            }

            // Vérifier que le nom du compte est unique pour cet utilisateur
            $existingAccountWithName = \App\Models\Compte::where('utilisateur_id', $user->id)
                                            ->where('nom_compte', $data['nom_compte'])
                                            ->first();
            if ($existingAccountWithName) {
                return $this->errorResponse('Un compte avec ce nom existe déjà pour cet utilisateur.', 400);
            }

            // Auto-générer tous les champs requis pour les comptes secondaires
            $data['numero_compte'] = 'CMPT-' . strtoupper(uniqid());
            $data['client_id'] = $user->id; // Utilise l'ID de l'utilisateur comme client_id
            $data['type_compte'] = 'courant'; // Type par défaut
            $data['devise'] = 'XOF'; // Devise par défaut
            $data['statut'] = 'inactif'; // Les comptes secondaires sont créés inactifs
            $data['titulaire'] = $user->nom . ' ' . $user->prenom; // Nom complet de l'utilisateur
            $data['utilisateur_id'] = $user->id;

            $compte = $this->compteService->create($data);

            $this->adminService->logAdminAction(Auth::id(), 'create_compte_for_user', $user->id, $data);

            return $this->respondCreated($compte, 'Compte secondaire "' . $data['nom_compte'] . '" créé avec succès pour l\'utilisateur.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getAllComptes(Request $request)
    {
        try {
            $query = \App\Models\Compte::query();

            // Filtre par statut
            if ($request->has('statut') && !empty($request->statut)) {
                $query->where('statut', $request->statut);
            }

            // Note: Solde filtering removed as solde is now calculated

            // Filtre par numéro de compte
            if ($request->has('numero_compte') && !empty($request->numero_compte)) {
                $query->where('numero_compte', 'like', '%' . $request->numero_compte . '%');
            }

            // Filtre par titulaire
            if ($request->has('titulaire') && !empty($request->titulaire)) {
                $query->where('titulaire', 'like', '%' . $request->titulaire . '%');
            }

            // Recherche globale dans numéro, titulaire et code marchand
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('numero_compte', 'like', '%' . $searchTerm . '%')
                      ->orWhere('titulaire', 'like', '%' . $searchTerm . '%')
                      ->orWhere('code_marchand', 'like', '%' . $searchTerm . '%');
                });
            }

            $comptes = $this->getPaginatedSorted($query, $request);

            // Formater les données de sortie (seulement les champs métier)
            $formattedData = $comptes->getCollection()->map(function ($compte) {
                return [
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'solde' => $compte->solde,
                    'statut' => $compte->statut,
                    'code_marchand' => $compte->code_marchand,
                ];
            });

            // Remplacer la collection dans l'objet paginé
            $comptes->setCollection($formattedData);

            return $this->successResponse($comptes, 'Comptes récupérés avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
