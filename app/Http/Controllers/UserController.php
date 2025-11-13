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
     * @OA\Post(
     *     path="/suppliers/balance-request",
     *     operationId="requestBalance",
     *     tags={"Fournisseurs"},
     *     summary="Demander l'achat de solde (fournisseur)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant"},
     *             @OA\Property(property="montant", type="number", format="float", example=100000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Demande créée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Demande de solde créée avec succès")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès réservé aux fournisseurs approuvés")
     * )
     */
    public function requestBalance(Request $request)
    {
        /** @var \Illuminate\Http\Request $request */
        $user = auth('api')->user();
        if (!$user || !(in_array($user->type, ['commercant', 'fournisseur'])) || $user->statut !== 'actif') {
            return $this->errorResponse(MessagesErreursRequests::SUPPLIER_ACCESS_DENIED->value, 403);
        }

        $data = $request->validate([
            MessagesErreursRequests::VALIDATION_MONTANT->value => MessagesErreursRequests::VALIDATION_MONTANT_RULES->value,
        ]);

        $balanceRequest = BalanceRequest::create([
            'supplier_id' => $user->id,
            'montant' => $data['montant'],
            'statut' => BalanceRequestStatus::EN_ATTENTE->value,
        ]);

        return $this->respondCreated($balanceRequest, ResponseMessage::BALANCE_REQUEST_CREATED->value);
    }

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

}
