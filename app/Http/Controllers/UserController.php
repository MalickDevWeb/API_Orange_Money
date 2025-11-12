<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BalanceRequest;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Endpoints pour l'administration des utilisateurs"
 * )
 */
class UserController extends Controller
{
    use ApiResponseTrait;


    /**
     * @OA\Post(
     *     path="/api/suppliers/balance-request",
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
        $user = auth('api')->user();
        if (!$user || !in_array($user->type, ['commercant', 'fournisseur']) || $user->statut !== 'actif') {
            return $this->errorResponse('Accès réservé aux fournisseurs approuvés', 403);
        }

        $data = $request->validate([
            'montant' => 'required|numeric|min:1000',
        ]);

        $balanceRequest = BalanceRequest::create([
            'supplier_id' => $user->id,
            'montant' => $data['montant'],
            'statut' => 'en_attente',
        ]);

        return $this->respondCreated($balanceRequest, 'Demande de solde créée avec succès');
    }

}
