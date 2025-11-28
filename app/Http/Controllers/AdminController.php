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
use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
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
    protected BrevoServiceInterface $brevoService;

    public function __construct(AdminService $adminService, CompteServiceInterface $compteService, BrevoServiceInterface $brevoService)
    {
        $this->adminService = $adminService;
        $this->compteService = $compteService;
        $this->brevoService = $brevoService;
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

            // Envoyer un email de confirmation à l'utilisateur
            if ($user->email) {
                $subject = "Inscription approuvée - {$user->nom} {$user->prenom}";
                $message = "Félicitations ! Votre inscription en tant que {$user->type} a été approuvée par l'administrateur. Vous pouvez maintenant accéder à toutes les fonctionnalités de l'application.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'user_approval'
                ])->render();

                try {
                    Mail::html($htmlContent, function ($mail) use ($user, $subject) {
                        $mail->to($user->email)
                             ->subject($subject);
                    });
                    \Illuminate\Support\Facades\Log::info('Email d\'approbation envoyé via Laravel Mail', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email approbation', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Envoyer un email de notification à l'admin
            $admin = Auth::user();
            if ($admin && $admin->email) {
                $subjectAdmin = "Approbation d'utilisateur - {$user->nom} {$user->prenom}";
                $messageAdmin = "Vous avez approuvé l'inscription de {$user->nom} {$user->prenom} ({$user->telephone}). L'utilisateur a été notifié par email.";
                $htmlContentAdmin = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $messageAdmin,
                    'role' => 'admin_confirmation'
                ])->render();

                try {
                    Mail::html($htmlContentAdmin, function ($mail) use ($admin, $subjectAdmin) {
                        $mail->to($admin->email)
                             ->subject($subjectAdmin);
                    });
                    \Illuminate\Support\Facades\Log::info('Email de confirmation d\'approbation envoyé à l\'admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'approved_user_id' => $user->id
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email confirmation admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            if ($user->email) {
                $subject = "Inscription approuvée - {$user->nom} {$user->prenom}";
                $message = "Félicitations ! Votre inscription en tant que {$user->type} a été approuvée par l'administrateur. Vous pouvez maintenant accéder à toutes les fonctionnalités de l'application.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'user_approval'
                ])->render();

                try {
                    Mail::html($htmlContent, function ($mail) use ($user, $subject) {
                        $mail->to($user->email)
                             ->subject($subject);
                    });
                    \Illuminate\Support\Facades\Log::info('Email d\'approbation envoyé via Laravel Mail', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email approbation', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $this->successResponse(null, ResponseMessage::USER_APPROVED->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

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

            // Envoyer un email de rejet à l'utilisateur
            if ($user->email) {
                $subject = "Inscription rejetée - {$user->nom} {$user->prenom}";
                $motif = $request->motif_rejet ?? 'Aucun motif spécifié';
                $message = "Nous regrettons de vous informer que votre inscription en tant que {$user->type} a été rejetée par l'administrateur. Motif : {$motif}. Vous pouvez contacter le support pour plus d'informations.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'user_rejection'
                ])->render();

                try {
                    Mail::html($htmlContent, function ($mail) use ($user, $subject) {
                        $mail->to($user->email)
                             ->subject($subject);
                    });
                    \Illuminate\Support\Facades\Log::info('Email de rejet envoyé via Laravel Mail', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email rejet', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Envoyer un email de notification à l'admin
            $admin = Auth::user();
            if ($admin && $admin->email) {
                $subjectAdmin = "Rejet d'utilisateur - {$user->nom} {$user->prenom}";
                $motifAdmin = $request->motif_rejet ?? 'Aucun motif spécifié';
                $messageAdmin = "Vous avez rejeté l'inscription de {$user->nom} {$user->prenom} ({$user->telephone}). Motif : {$motifAdmin}. L'utilisateur a été notifié par email.";
                $htmlContentAdmin = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $messageAdmin,
                    'role' => 'admin_confirmation'
                ])->render();

                try {
                    Mail::html($htmlContentAdmin, function ($mail) use ($admin, $subjectAdmin) {
                        $mail->to($admin->email)
                             ->subject($subjectAdmin);
                    });
                    \Illuminate\Support\Facades\Log::info('Email de confirmation de rejet envoyé à l\'admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'rejected_user_id' => $user->id
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email confirmation admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            if ($user->email) {
                $subject = "Inscription rejetée - {$user->nom} {$user->prenom}";
                $motif = $request->motif_rejet ?? 'Aucun motif spécifié';
                $message = "Nous regrettons de vous informer que votre inscription en tant que {$user->type} a été rejetée par l'administrateur. Motif : {$motif}. Vous pouvez contacter le support pour plus d'informations.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'user_rejection'
                ])->render();

                try {
                    Mail::html($htmlContent, function ($mail) use ($user, $subject) {
                        $mail->to($user->email)
                             ->subject($subject);
                    });
                    \Illuminate\Support\Facades\Log::info('Email de rejet envoyé via Laravel Mail', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email rejet', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

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
     * @OA\Get(
     *     path="/admin/actions",
     *     operationId="getAdminActions",
     *     tags={"Administration"},
     *     summary="Récupérer l'historique des actions administrateur",
     *     description="Liste paginée des actions admin avec filtrage par type, admin, utilisateur cible et dates",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="action_type",
     *         in="query",
     *         description="Filtrer par type d'action",
     *         required=false,
     *         @OA\Schema(type="string", example="approve_user")
     *     ),
     *     @OA\Parameter(
     *         name="admin_id",
     *         in="query",
     *         description="Filtrer par ID d'admin",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="target_user_id",
     *         in="query",
     *         description="Filtrer par ID d'utilisateur cible",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page (1-100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique des actions récupéré",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Actions admin récupérées"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="admin", type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="nom", type="string"),
     *                         @OA\Property(property="prenom", type="string")
     *                     ),
     *                     @OA\Property(property="action_type", type="string", example="approve_user"),
     *                     @OA\Property(property="target_user", type="object", nullable=true,
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="nom", type="string"),
     *                         @OA\Property(property="prenom", type="string"),
     *                         @OA\Property(property="telephone", type="string")
     *                     ),
     *                     @OA\Property(property="details", type="object", example={"action": "approve"}),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé")
     * )
     */
    public function getAdminActions(Request $request)
    {
        return $this->tryCatch(function () use ($request) {
            $query = \App\Models\AdminAction::with(['admin', 'targetUser']);

            // Filtre par type d'action
            if ($request->has('action_type') && !empty($request->action_type)) {
                $query->where('action_type', $request->action_type);
            }

            // Filtre par admin
            if ($request->has('admin_id') && !empty($request->admin_id)) {
                $query->where('admin_id', $request->admin_id);
            }

            // Filtre par utilisateur cible
            if ($request->has('target_user_id') && !empty($request->target_user_id)) {
                $query->where('target_user_id', $request->target_user_id);
            }

            // Filtre par date
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $actions = $this->getPaginatedSorted($query, $request, 'created_at', 'desc');

            // Formater les données de sortie
            $formattedData = $actions->getCollection()->map(function ($action) {
                return [
                    'id' => $action->id,
                    'admin' => $action->admin ? [
                        'id' => $action->admin->id,
                        'nom' => $action->admin->nom,
                        'prenom' => $action->admin->prenom,
                    ] : null,
                    'action_type' => $action->action_type,
                    'target_user' => $action->targetUser ? [
                        'id' => $action->targetUser->id,
                        'nom' => $action->targetUser->nom,
                        'prenom' => $action->targetUser->prenom,
                        'telephone' => $action->targetUser->telephone,
                    ] : null,
                    'details' => $action->details,
                    'created_at' => $action->created_at,
                ];
            });

            // Remplacer la collection dans l'objet paginé
            $actions->setCollection($formattedData);

            return $actions;
        }, 'Actions admin récupérées');
    }


    /**
     * @OA\Post(
     *     path="/admin/users/{telephone}/action",
     *     operationId="userAction",
     *     tags={"Administration"},
     *     summary="Effectuer une action sur un utilisateur (approuver, rejeter, suspendre, bannir, dépôt)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="770000040"),
     *         description="Numéro de téléphone de l'utilisateur"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"approve","reject","suspend","unsuspend","ban","unban","delete","deposit"}, example="approve", description="Action à effectuer"),
     *             @OA\Property(property="motif_rejet", type="string", example="Documents insuffisants", description="Requis pour reject"),
     *             @OA\Property(property="montant", type="number", format="float", example=50000, description="Requis pour deposit"),
     *             @OA\Property(property="note", type="string", example="Dépôt client", description="Optionnel pour deposit")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Action effectuée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Utilisateur approuvé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Action invalide ou déjà effectuée"),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
      */
     public function userAction(Request $request, $telephone)
     {
         try {
             $telephone = trim($telephone, '"');
             $user = User::where('telephone', $telephone)->firstOrFail();

             $data = $request->validate([
                 'action' => 'required|string|in:approve,reject,suspend,unsuspend,ban,unban,delete,deposit',
                 'motif_rejet' => 'nullable|string',
                 'montant' => 'nullable|numeric|min:0.01',
                 'note' => 'nullable|string'
             ]);

             $action = $data['action'];

             switch ($action) {
                 case 'approve':
                     if (!($user->isCommercant() || $user->isFournisseur())) {
                         return $this->errorResponse(MessagesErreursRequests::APPROVABLE_TYPE_ERROR->value, 400);
                     }
                     if ($user->statut === UserStatus::ACTIF->value) {
                         return $this->errorResponse('Utilisateur déjà approuvé', 400);
                     }
                     $user->update(['statut' => UserStatus::ACTIF->value]);
                     $message = 'Utilisateur approuvé avec succès';
                     $this->sendUserNotificationEmail($user, 'approval');
                     break;

                 case 'reject':
                     if (!($user->isCommercant() || $user->isFournisseur())) {
                         return $this->errorResponse(MessagesErreursRequests::REJECTABLE_TYPE_ERROR->value, 400);
                     }
                     if ($user->statut === UserStatus::INACTIF->value) {
                         return $this->errorResponse('Utilisateur déjà rejeté', 400);
                     }
                     $user->update(['statut' => UserStatus::INACTIF->value]);
                     $message = 'Utilisateur rejeté';
                     $this->sendUserNotificationEmail($user, 'rejection', $data['motif_rejet'] ?? null);
                     break;

                 case 'suspend':
                     if ($user->statut === UserStatus::SUSPENDU->value) {
                         return $this->errorResponse('Utilisateur déjà suspendu', 400);
                     }
                     $user->update(['statut' => UserStatus::SUSPENDU->value]);
                     $message = 'Utilisateur suspendu';
                     break;

                 case 'unsuspend':
                     if ($user->statut !== UserStatus::SUSPENDU->value) {
                         return $this->errorResponse('Utilisateur n\'est pas suspendu', 400);
                     }
                     $user->update(['statut' => UserStatus::ACTIF->value]);
                     $message = 'Utilisateur réactivé';
                     break;

                 case 'ban':
                     $success = $this->adminService->banUser($user->id);
                     if (!$success) {
                         return $this->errorResponse('Utilisateur non trouvé', 404);
                     }
                     $message = 'Utilisateur banni';
                     break;

                 case 'unban':
                     $success = $this->adminService->unbanUser($user->id);
                     if (!$success) {
                         return $this->errorResponse('Utilisateur non trouvé', 404);
                     }
                     $message = 'Utilisateur débanni';
                     break;

                 case 'delete':
                     $user->delete(); // Soft delete
                     $message = 'Utilisateur supprimé';
                     break;

                 case 'deposit':
                     // Validation du montant
                     if (empty($data['montant'])) {
                         return $this->errorResponse('Montant requis pour effectuer un dépôt', 400);
                     }

                     // Vérifier que c'est un client
                     if ($user->type !== UserType::CLIENT->value) {
                         return $this->errorResponse('Seuls les clients peuvent recevoir des dépôts', 400);
                     }

                     // Trouver le compte du client
                     $compte = $user->comptes->first();
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

                     $message = 'Dépôt effectué avec succès';
                     break;
             }

             // Log admin action
             $this->adminService->logAdminAction(Auth::id(), $action . '_user', $user->id, $data);

             // Send confirmation email to admin
             $this->sendAdminConfirmationEmail($action, $user, $data['motif_rejet'] ?? null);

             return $this->successResponse(null, $message);
         } catch (\Exception $e) {
             return $this->errorResponse($e->getMessage());
         }
     }

     /**
      * @OA\Post(
      *     path="/admin/balance-requests/{telephone}/action",
      *     operationId="balanceRequestAction",
      *     tags={"Administration"},
      *     summary="Effectuer une action sur une demande de solde (approuver, rejeter)",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="telephone",
      *         in="path",
      *         required=true,
      *         @OA\Schema(type="string", example="771234567"),
      *         description="Numéro de téléphone du fournisseur"
      *     ),
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"action"},
      *             @OA\Property(property="action", type="string", enum={"approve","reject"}, example="approve", description="Action à effectuer sur la demande de solde"),
      *             @OA\Property(property="motif_rejet", type="string", example="Montant trop élevé", description="Requis pour reject")
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Action effectuée avec succès",
      *         @OA\JsonContent(
      *             @OA\Property(property="status", type="string", example="success"),
      *             @OA\Property(property="message", type="string", example="Demande de solde approuvée")
      *         )
      *     ),
      *     @OA\Response(response=400, description="Action invalide ou motif manquant"),
      *     @OA\Response(response=403, description="Non autorisé"),
      *     @OA\Response(response=404, description="Demande de solde non trouvée")
      * )
      */
     public function balanceRequestAction(Request $request, $telephone)
     {
         try {
             $telephone = trim($telephone, '"');

             $data = $request->validate([
                 'action' => 'required|string|in:approve,reject',
                 'motif_rejet' => 'nullable|string'
             ]);

             $action = $data['action'];

             // Trouver la demande de solde en attente pour ce numéro de téléphone
             $balanceRequest = BalanceRequest::whereHas('supplier', function($q) use ($telephone) {
                 $q->where('telephone', $telephone);
             })->where('statut', BalanceRequestStatus::EN_ATTENTE->value)->first();

             if (!$balanceRequest) {
                 // Vérifier s'il y a une demande déjà traitée
                 $existingRequest = BalanceRequest::whereHas('supplier', function($q) use ($telephone) {
                     $q->where('telephone', $telephone);
                 })->latest()->first();

                 if ($existingRequest) {
                     if ($existingRequest->statut === BalanceRequestStatus::APPROUVEE->value) {
                         if ($action === 'approve') {
                             return $this->errorResponse('Cette demande de solde a déjà été approuvée', 400);
                         } else {
                             return $this->errorResponse('Cette demande de solde a déjà été approuvée, vous ne pouvez pas la rejeter', 400);
                         }
                     } elseif ($existingRequest->statut === BalanceRequestStatus::REJETEE->value) {
                         if ($action === 'reject') {
                             return $this->errorResponse('Cette demande de solde a déjà été rejetée', 400);
                         } else {
                             return $this->errorResponse('Cette demande de solde a déjà été rejetée, vous ne pouvez pas l\'approuver', 400);
                         }
                     }
                 }

                 return $this->errorResponse('Aucune demande de solde en attente trouvée pour ce numéro', 404);
             }

             $supplier = $balanceRequest->supplier;

             switch ($action) {
                case 'approve':
                    $balanceRequest->update([
                        'statut' => BalanceRequestStatus::APPROUVEE->value,
                        'admin_id' => Auth::id(),
                        'traitee_at' => now()
                    ]);

                    // Créer une transaction de dépôt pour ajouter le montant au solde du fournisseur
                    if ($supplier && $supplier->comptes->count() > 0) {
                        $compte = $supplier->comptes->first();
                        \App\Models\Transaction::create([
                            'type' => TransactionType::DEPOT->value,
                            'montant' => $balanceRequest->montant,
                            'reference' => 'DEP-APPROVAL-' . strtoupper(uniqid()),
                            'statut' => TransactionStatus::REUSSIE->value,
                            'note' => 'Approbation de demande de solde',
                            'compte_emetteur_id' => null,
                            'compte_recepteur_id' => $compte->id,
                            'date_transaction' => now(),
                        ]);
                    }

                    // Envoyer un email de confirmation au fournisseur
                    $this->sendBalanceRequestNotificationEmail($supplier, 'approved', $balanceRequest->montant);

                    $message = 'Demande de solde approuvée';
                    break;

                case 'reject':
                    // Validation du motif de rejet
                    if (empty($data['motif_rejet'])) {
                        return $this->errorResponse('Motif de rejet requis pour rejeter une demande de solde', 400);
                    }

                    $balanceRequest->update([
                        'statut' => BalanceRequestStatus::REJETEE->value,
                        'motif_rejet' => $data['motif_rejet'],
                        'admin_id' => Auth::id(),
                        'traitee_at' => now()
                    ]);

                    // Envoyer un email de rejet au fournisseur
                    $this->sendBalanceRequestNotificationEmail($supplier, 'rejected', null, $data['motif_rejet']);

                    $message = 'Demande de solde rejetée';
                    break;
            }

             // Log admin action
             $this->adminService->logAdminAction(Auth::id(), $action . '_balance_request', $balanceRequest->supplier->id, $data);

             // Send confirmation email to admin
             $this->sendAdminConfirmationEmail($action . '_balance_request', $balanceRequest->supplier, $data['motif_rejet'] ?? null);

             return $this->successResponse(null, $message);
         } catch (\Exception $e) {
             return $this->errorResponse($e->getMessage());
         }
     }

    private function sendUserNotificationEmail(User $user, string $type, ?string $motif = null)
    {
        if (!$user->email) return;

        $subject = $type === 'approval' ? "Inscription approuvée - {$user->nom} {$user->prenom}" : "Inscription rejetée - {$user->nom} {$user->prenom}";
        $message = $type === 'approval'
            ? "Félicitations ! Votre inscription en tant que {$user->type} a été approuvée par l'administrateur. Vous pouvez maintenant accéder à toutes les fonctionnalités de l'application."
            : "Nous regrettons de vous informer que votre inscription en tant que {$user->type} a été rejetée par l'administrateur. Motif : {$motif}. Vous pouvez contacter le support pour plus d'informations.";

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => null,
            'message' => $message,
            'role' => $type === 'approval' ? 'user_approval' : 'user_rejection'
        ])->render();

        try {
            Mail::html($htmlContent, function ($mail) use ($user, $subject) {
                $mail->to($user->email)->subject($subject);
            });
            \Illuminate\Support\Facades\Log::info("Email de {$type} envoyé", ['user_id' => $user->id, 'email' => $user->email]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email {$type}", ['user_id' => $user->id, 'email' => $user->email, 'error' => $e->getMessage()]);
        }
    }

    private function sendBalanceRequestNotificationEmail(User $supplier, string $type, ?float $amount = null, ?string $motif = null)
    {
        if (!$supplier->email) return;

        if ($type === 'approved') {
            $subject = "Demande de solde approuvée - {$supplier->nom} {$supplier->prenom}";
            $message = "Félicitations ! Votre demande de solde de " . number_format($amount, 0, ',', ' ') . " XOF a été approuvée par l'administrateur. Le montant a été ajouté à votre compte.";
        } else {
            $subject = "Demande de solde rejetée - {$supplier->nom} {$supplier->prenom}";
            $message = "Nous regrettons de vous informer que votre demande de solde a été rejetée par l'administrateur. Motif : {$motif}. Vous pouvez contacter le support pour plus d'informations.";
        }

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => null,
            'message' => $message,
            'role' => 'balance_request_' . $type
        ])->render();

        try {
            Mail::html($htmlContent, function ($mail) use ($supplier, $subject) {
                $mail->to($supplier->email)->subject($subject);
            });
            \Illuminate\Support\Facades\Log::info("Email de {$type} de demande de solde envoyé", ['supplier_id' => $supplier->id, 'email' => $supplier->email]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email {$type} demande de solde", ['supplier_id' => $supplier->id, 'email' => $supplier->email, 'error' => $e->getMessage()]);
        }
    }

    private function sendAdminConfirmationEmail(string $action, User $user, ?string $motif = null)
    {
        $admin = Auth::user();
        if (!$admin || !$admin->email) return;

        $actionLabels = [
            'approve' => 'approbation',
            'reject' => 'rejet',
            'suspend' => 'suspension',
            'unsuspend' => 'réactivation',
            'ban' => 'bannissement',
            'unban' => 'débannissement',
            'delete' => 'suppression',
            'deposit' => 'dépôt',
            'approve_balance_request' => 'approbation de demande de solde',
            'reject_balance_request' => 'rejet de demande de solde'
        ];

        $subject = ucfirst($actionLabels[$action] ?? $action) . " d'utilisateur - {$user->nom} {$user->prenom}";
        $message = "Vous avez effectué l'action '{$actionLabels[$action]}' sur l'utilisateur {$user->nom} {$user->prenom} ({$user->telephone}).";
        if ($motif) $message .= " Motif : {$motif}.";

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => null,
            'message' => $message,
            'role' => 'admin_confirmation'
        ])->render();

        try {
            Mail::html($htmlContent, function ($mail) use ($admin, $subject) {
                $mail->to($admin->email)->subject($subject);
            });
            \Illuminate\Support\Facades\Log::info("Email de confirmation {$action} envoyé à l'admin", ['admin_id' => $admin->id, 'user_id' => $user->id]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email confirmation admin", ['admin_id' => $admin->id, 'error' => $e->getMessage()]);
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
            $this->adminService->logAdminAction((int) Auth::id(), 'update_global_fees', null, $request->all());
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

            // Vérifier que le nom du compte est unique pour cet utilisateur (insensible à la casse)
            $existingAccountWithName = \App\Models\Compte::where('utilisateur_id', $user->id)
                                            ->whereRaw('LOWER(nom_compte) = LOWER(?)', [$data['nom_compte']])
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
