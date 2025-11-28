<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BalanceRequest;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\ResponseMessage;
use App\Enums\MessagesErreursRequests;
use App\Enums\BalanceRequestStatus;

/**
 * @OA\Tag(
 *     name="Utilisateurs",
 *     description="Endpoints pour la gestion des utilisateurs"
 * )
 */
class UserController extends Controller
{
    use ApiResponseTrait;



    /**
     * @OA\Put(
     *     path="/user",
     *     operationId="updateProfile",
     *     tags={"Utilisateurs"},
     *     summary="Mettre à jour le profil utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="current_pin", type="string", example="1234", description="PIN actuel requis pour changer le PIN"),
     *             @OA\Property(property="new_pin", type="string", example="5678", description="Nouveau PIN (4 chiffres)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function updateProfile(Request $request)
    {
        /** @var \Illuminate\Http\Request $request */
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $data = $request->validate([
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'current_pin' => 'required_with:new_pin|string|size:4',
            'new_pin' => 'nullable|string|size:4|regex:/^[0-9]{4}$/',
        ]);

        // Vérifier le PIN actuel si changement de PIN
        if (isset($data['new_pin'])) {
            if (!$user->pin || $user->pin !== $data['current_pin']) {
                return $this->errorResponse('PIN actuel incorrect', 400);
            }
            $data['pin'] = $data['new_pin'];
            unset($data['current_pin'], $data['new_pin']);
        } else {
            unset($data['current_pin'], $data['new_pin']);
        }

        // Ne pas permettre la modification de certains champs
        unset($data['type'], $data['statut'], $data['telephone']);

        $user->update($data);

        return $this->successResponse($user, ResponseMessage::PROFILE_UPDATED->value);
    }

    /**
     * @OA\Get(
     *     path="/user/details",
     *     operationId="getUserDetails",
     *     tags={"Utilisateurs"},
     *     summary="Récupérer les détails de l'utilisateur avec comptes et transactions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Détails utilisateur récupérés",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Détails utilisateur récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="nom", type="string", example="Dupont"),
     *                 @OA\Property(property="prenom", type="string", example="Jean"),
     *                 @OA\Property(property="numero", type="string", example="771234567"),
     *                 @OA\Property(property="comptes", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *                 @OA\Property(property="compte_actif", ref="#/components/schemas/Compte"),
     *                 @OA\Property(property="solde_compte_actif", type="number", format="float", example="1500.50"),
     *                 @OA\Property(property="transactions_compte_actif", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */

    /**
     * @OA\Get(
     *     path="/user/sync",
     *     operationId="getUserSyncData",
     *     tags={"Utilisateurs"},
     *     summary="Récupérer uniquement les données de synchronisation (solde + dernières transactions)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Données de sync récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde_compte_actif", type="number", format="float", example="1500.50"),
     *                 @OA\Property(property="transactions_count", type="integer", example="25"),
     *                 @OA\Property(property="latest_transaction", ref="#/components/schemas/Transaction")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function detailsUser()
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        // OPTIMISATION: Eager loading avec contraintes pour éviter N+1 queries
        $user->load([
            'comptes' => function ($query) {
                $query->where('statut', 'actif') // Uniquement comptes actifs
                      ->select(['id', 'numero_compte', 'nom_compte', 'statut', 'utilisateur_id']); // Champs nécessaires uniquement (solde calculé dynamiquement)
            },
            'comptes.transactionsEmises' => function ($query) {
                $query->with([
                    'compteEmetteur:id,numero_compte,utilisateur_id',
                    'compteRecepteur:id,numero_compte,utilisateur_id',
                    'compteEmetteur.utilisateur:id,nom,prenom,telephone',
                    'compteRecepteur.utilisateur:id,nom,prenom,telephone'
                ])
                ->where('statut', 'reussie') // Uniquement transactions réussies
                ->latest('created_at')
                ->limit(10); // Limiter à 10 transactions émises récentes
            },
            'comptes.transactionsRecues' => function ($query) {
                $query->with([
                    'compteEmetteur:id,numero_compte,utilisateur_id',
                    'compteRecepteur:id,numero_compte,utilisateur_id',
                    'compteEmetteur.utilisateur:id,nom,prenom,telephone',
                    'compteRecepteur.utilisateur:id,nom,prenom,telephone'
                ])
                ->where('statut', 'reussie') // Uniquement transactions réussies
                ->latest('created_at')
                ->limit(10); // Limiter à 10 transactions reçues récentes
            }
        ]);

        // Récupérer le premier compte actif (optimisé)
        $compteActif = $user->comptes->first();

        // OPTIMISATION: Fusionner et limiter les transactions en mémoire (déjà limité côté DB)
        $transactionsCompteActif = collect();
        $soldeCompteActif = null;

        if ($compteActif) {
            $emises = $compteActif->transactionsEmises ?? collect();
            $recues = $compteActif->transactionsRecues ?? collect();

            // Fusionner et prendre les 20 plus récentes
            $transactionsCompteActif = $emises->merge($recues)
                ->sortByDesc('created_at')
                ->take(20)
                ->map(function ($transaction) {
                    // Convertir en array et ajouter les données des relations pour l'affichage frontend
                    $transactionData = $transaction->toArray();
                    $transactionData['compte_emetteur_data'] = $transaction->compteEmetteur ? [
                        'id' => $transaction->compteEmetteur->id,
                        'numero_compte' => $transaction->compteEmetteur->numero_compte,
                        'nom_compte' => $transaction->compteEmetteur->nom_compte,
                        'code_marchand' => $transaction->compteEmetteur->code_marchand,
                    ] : null;
                    $transactionData['compte_recepteur_data'] = $transaction->compteRecepteur ? [
                        'id' => $transaction->compteRecepteur->id,
                        'numero_compte' => $transaction->compteRecepteur->numero_compte,
                        'nom_compte' => $transaction->compteRecepteur->nom_compte,
                        'code_marchand' => $transaction->compteRecepteur->code_marchand,
                    ] : null;
                    $transactionData['utilisateur_emetteur_data'] = $transaction->compteEmetteur && $transaction->compteEmetteur->utilisateur ? [
                        'id' => $transaction->compteEmetteur->utilisateur->id,
                        'nom' => $transaction->compteEmetteur->utilisateur->nom,
                        'prenom' => $transaction->compteEmetteur->utilisateur->prenom,
                        'telephone' => $transaction->compteEmetteur->utilisateur->telephone,
                        'type' => $transaction->compteEmetteur->utilisateur->type,
                    ] : null;
                    $transactionData['utilisateur_recepteur_data'] = $transaction->compteRecepteur && $transaction->compteRecepteur->utilisateur ? [
                        'id' => $transaction->compteRecepteur->utilisateur->id,
                        'nom' => $transaction->compteRecepteur->utilisateur->nom,
                        'prenom' => $transaction->compteRecepteur->utilisateur->prenom,
                        'telephone' => $transaction->compteRecepteur->utilisateur->telephone,
                        'type' => $transaction->compteRecepteur->utilisateur->type,
                    ] : null;
                    return $transactionData;
                })
                ->values(); // Réindexer la collection

            // OPTIMISATION: Calculer le solde directement sans passer par l'accessor lent
            $received = $compteActif->transactionsRecues()->where('statut', 'reussie')->sum('montant');
            $sent = $compteActif->transactionsEmises()->where('statut', 'reussie')->sum('montant');
            $soldeCompteActif = $received - $sent;
        }

        $data = [
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'numero' => $user->telephone,
            'comptes' => $user->comptes,
            'compte_actif' => $compteActif,
            'solde_compte_actif' => $soldeCompteActif,
            'transactions_compte_actif' => $transactionsCompteActif,
        ];

        return $this->successResponse($data, 'Détails utilisateur récupérés avec succès');
    }

    /**
     * Récupérer uniquement les données de synchronisation (optimisé pour les vérifications périodiques)
     */
    public function syncUserData()
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        // Trouver le compte actif
        $compteActif = $user->comptes()->where('statut', 'actif')->first();

        $syncData = [
            'solde_compte_actif' => $compteActif ? $compteActif->solde : 0,
            'transactions_count' => $compteActif ?
                $compteActif->transactionsEmises()->count() + $compteActif->transactionsRecues()->count() : 0,
        ];

        // Ajouter la dernière transaction si elle existe
        if ($compteActif) {
            $latestTransaction = $compteActif->transactionsEmises()
                ->orWhere('compte_recepteur_id', $compteActif->id)
                ->with(['compteEmetteur', 'compteRecepteur'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestTransaction) {
                $syncData['latest_transaction'] = $latestTransaction->toArray();
                $syncData['latest_transaction']['compte_emetteur_data'] = $latestTransaction->compteEmetteur ? [
                    'numero_compte' => $latestTransaction->compteEmetteur->numero_compte,
                    'nom_compte' => $latestTransaction->compteEmetteur->nom_compte,
                ] : null;
                $syncData['latest_transaction']['compte_recepteur_data'] = $latestTransaction->compteRecepteur ? [
                    'numero_compte' => $latestTransaction->compteRecepteur->numero_compte,
                    'nom_compte' => $latestTransaction->compteRecepteur->nom_compte,
                ] : null;
            }
        }

        return $this->successResponse($syncData, 'Données de synchronisation récupérées');
    }

}
