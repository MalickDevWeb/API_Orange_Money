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
    public function detailsUser()
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        // Charger les comptes de l'utilisateur
        $comptes = $user->comptes()->get();

        // Trouver le compte actif (statut = 'actif')
        $compteActif = $comptes->firstWhere('statut', 'actif');

        $transactionsCompteActif = collect();

        if ($compteActif) {
            // Récupérer les transactions où le compte actif est émetteur ou récepteur
            $transactionsEmises = $compteActif->transactionsEmises()->with(['compteEmetteur', 'compteRecepteur'])->get();
            $transactionsRecues = $compteActif->transactionsRecues()->with(['compteEmetteur', 'compteRecepteur'])->get();

            // Fusionner et trier par date
            $transactionsCompteActif = $transactionsEmises->merge($transactionsRecues)->sortByDesc('created_at');
        }

        $data = [
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'numero' => $user->telephone,
            'comptes' => $comptes,
            'compte_actif' => $compteActif,
            'solde_compte_actif' => $compteActif ? $compteActif->solde : null,
            'transactions_compte_actif' => $transactionsCompteActif,
        ];

        return $this->successResponse($data, 'Détails utilisateur récupérés avec succès');
    }

}
