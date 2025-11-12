<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BalanceRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Endpoints pour les administrateurs"
 * )
 */
class AdminController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->type !== 'admin') {
                return $this->errorResponse('Accès non autorisé', 403);
            }
            return $next($request);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users/pending",
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
        try {
            $users = User::whereIn('type', ['commercant', 'fournisseur'])
                        ->where('statut', 'en_attente')
                        ->get();

            return $this->successResponse($users, 'Utilisateurs en attente récupérés');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{id}/approve",
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
            $user = User::findOrFail($id);

            if (!in_array($user->type, ['commercant', 'fournisseur'])) {
                return $this->errorResponse('Type d\'utilisateur non approuvable', 400);
            }

            $user->update(['statut' => 'actif']);

            return $this->successResponse(null, 'Utilisateur approuvé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{id}/reject",
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
                'motif_rejet' => 'required|string|max:255'
            ]);

            $user = User::findOrFail($id);

            if (!in_array($user->type, ['commercant', 'fournisseur'])) {
                return $this->errorResponse('Type d\'utilisateur non rejetable', 400);
            }

            $user->update(['statut' => 'inactif']);

            // TODO: Peut-être stocker le motif de rejet quelque part

            return $this->successResponse(null, 'Utilisateur rejeté');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/balance-requests/pending",
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
        try {
            $requests = BalanceRequest::with('supplier')
                                    ->where('statut', 'en_attente')
                                    ->get();

            return $this->successResponse($requests, 'Demandes de solde en attente récupérées');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/balance-requests/{id}/approve",
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
            $request = BalanceRequest::findOrFail($id);

            $request->update([
                'statut' => 'approuvee',
                'admin_id' => Auth::id(),
                'traitee_at' => now()
            ]);

            // Créer une transaction de dépôt pour ajouter le montant au solde du fournisseur
            $supplier = $request->supplier;
            if ($supplier && $supplier->comptes->count() > 0) {
                $compte = $supplier->comptes->first();
                \App\Models\Transaction::create([
                    'type' => 'depot',
                    'montant' => $request->montant,
                    'reference' => 'DEP-APPROVAL-' . strtoupper(uniqid()),
                    'statut' => 'reussie',
                    'note' => 'Approbation de demande de solde',
                    'compte_emetteur_id' => null,
                    'compte_recepteur_id' => $compte->id,
                    'date_transaction' => now(),
                ]);
            }

            return $this->successResponse(null, 'Demande de solde approuvée');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/balance-requests/{id}/reject",
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
                'motif_rejet' => 'required|string|max:255'
            ]);

            $balanceRequest = BalanceRequest::findOrFail($id);

            $balanceRequest->update([
                'statut' => 'rejetee',
                'motif_rejet' => $request->motif_rejet,
                'admin_id' => Auth::id(),
                'traitee_at' => now()
            ]);

            return $this->successResponse(null, 'Demande de solde rejetée');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/deposit",
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
                'telephone' => 'required|string',
                'montant' => 'required|numeric|min:0.01',
                'note' => 'nullable|string',
            ]);

            // Trouver le client par téléphone
            $client = User::where('telephone', $data['telephone'])
                         ->where('type', 'client')
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
                'type' => 'depot',
                'montant' => $data['montant'],
                'reference' => 'DEP-ADMIN-' . strtoupper(uniqid()),
                'statut' => 'reussie',
                'note' => $data['note'] ?? 'Dépôt effectué par l\'admin',
                'compte_emetteur_id' => null,
                'compte_recepteur_id' => $compte->id,
                'date_transaction' => now(),
            ]);

            return $this->respondCreated(null, 'Dépôt effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
