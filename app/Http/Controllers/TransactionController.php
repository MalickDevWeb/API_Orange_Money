<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions financières"
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="uuid-transaction"),
 *     @OA\Property(property="type", type="string", enum={"depot","retrait","transfert","paiement"}, example="transfert"),
 *     @OA\Property(property="montant", type="number", format="float", example=50000),
 *     @OA\Property(property="reference", type="string", example="TXN-123456"),
 *     @OA\Property(property="statut", type="string", enum={"reussie","echouee","annulee"}, example="reussie"),
 *     @OA\Property(property="note", type="string", nullable=true, example="Paiement de facture"),
 *     @OA\Property(property="compte_emetteur_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="compte_recepteur_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="date_transaction", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\PathItem(
 *     path="/api/transactions"
 * )
 * @OA\PathItem(
 *     path="/api/transactions/{transaction}"
 * )
 * @OA\PathItem(
 *     path="/api/transactions/depot"
 * )
 * @OA\PathItem(
 *     path="/api/transactions/retrait"
 * )
 * @OA\PathItem(
 *     path="/api/transactions/transfert"
 * )
 * @OA\PathItem(
 *     path="/api/transactions/paiement"
 * )
 */
class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="Lister toutes les transactions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Transaction")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $transactions = $this->transactionService->getAll();
            return $this->successResponse($transactions, 'Transactions récupérées avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="Créer une nouvelle transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","montant","compte_emetteur_id","compte_recepteur_id"},
     *             @OA\Property(property="type", type="string", enum={"depot","retrait","transfert","paiement"}, example="transfert"),
     *             @OA\Property(property="montant", type="number", format="float", example=50000),
     *             @OA\Property(property="reference", type="string", example="TXN-123456"),
     *             @OA\Property(property="note", type="string", example="Paiement de facture"),
     *             @OA\Property(property="compte_emetteur_id", type="string", example="uuid-compte-emetteur"),
     *             @OA\Property(property="compte_recepteur_id", type="string", example="uuid-compte-recepteur")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'type' => 'required|string|in:depot,retrait,transfert,paiement',
                'montant' => 'required|numeric|min:0.01',
                'reference' => 'nullable|string|unique:transactions,reference',
                'note' => 'nullable|string',
                'compte_emetteur_id' => 'required|exists:comptes,id',
                'compte_recepteur_id' => 'required|exists:comptes,id|different:compte_emetteur_id',
            ]);

            // Générer une référence unique si non fournie
            if (empty($data['reference'])) {
                $data['reference'] = 'TXN-' . strtoupper(uniqid());
            }

            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);
            return $this->respondCreated($transaction, 'Transaction créée avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/{transaction}",
     *     tags={"Transactions"},
     *     summary="Afficher une transaction spécifique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID de la transaction"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée")
     * )
     */
    public function show(Transaction $transaction)
    {
        try {
            return $this->successResponse($transaction, 'Transaction récupérée avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/transactions/{transaction}",
     *     tags={"Transactions"},
     *     summary="Mettre à jour une transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID de la transaction"
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="note", type="string", example="Note mise à jour"),
     *             @OA\Property(property="statut", type="string", enum={"reussie","echouee","annulee"}, example="reussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction mise à jour",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     )
     * )
     */
    public function update(Request $request, Transaction $transaction)
    {
        try {
            $data = $request->validate([
                'note' => 'nullable|string',
                'statut' => 'nullable|string|in:reussie,echouee,annulee',
            ]);

            $transaction = $this->transactionService->update($transaction->id, $data);
            if (!$transaction) {
                return $this->errorResponse('Erreur lors de la mise à jour de la transaction');
            }

            return $this->successResponse($transaction, 'Transaction mise à jour avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/transactions/{transaction}",
     *     tags={"Transactions"},
     *     summary="Supprimer une transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID de la transaction"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction supprimée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transaction supprimée avec succès")
     *         )
     *     )
     * )
     */
    public function destroy(Transaction $transaction)
    {
        try {
            $result = $this->transactionService->delete($transaction->id);
            if (!$result) {
                return $this->errorResponse('Erreur lors de la suppression de la transaction');
            }

            return $this->successResponse(null, 'Transaction supprimée avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/depot",
     *     tags={"Transactions"},
     *     summary="Effectuer un dépôt sur un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant","compte_recepteur_id"},
     *             @OA\Property(property="montant", type="number", format="float", example=50000),
     *             @OA\Property(property="compte_recepteur_id", type="string", example="uuid-compte"),
     *             @OA\Property(property="note", type="string", example="Dépôt d'argent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     )
     * )
     */
    public function depot(Request $request)
    {
        try {
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'compte_recepteur_id' => 'required|exists:comptes,id',
                'note' => 'nullable|string',
            ]);

            // Pour un dépôt, l'émetteur est null (système/admin)
            $data['type'] = 'depot';
            $data['compte_emetteur_id'] = null;
            $data['reference'] = 'DEP-' . strtoupper(uniqid());
            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);
            return $this->respondCreated($transaction, 'Dépôt effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/retrait",
     *     tags={"Transactions"},
     *     summary="Effectuer un retrait d'un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant","compte_emetteur_id"},
     *             @OA\Property(property="montant", type="number", format="float", example=25000),
     *             @OA\Property(property="compte_emetteur_id", type="string", example="uuid-compte"),
     *             @OA\Property(property="note", type="string", example="Retrait d'argent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retrait effectué avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=400, description="Solde insuffisant")
     * )
     */
    public function retrait(Request $request)
    {
        try {
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'compte_emetteur_id' => 'required|exists:comptes,id',
                'note' => 'nullable|string',
            ]);

            // Vérifier le solde du compte
            $compte = Compte::find($data['compte_emetteur_id']);
            if ($compte->solde < $data['montant']) {
                return $this->errorResponse('Solde insuffisant pour effectuer ce retrait', 400);
            }

            // Pour un retrait, le récepteur est null (système/admin)
            $data['type'] = 'retrait';
            $data['compte_recepteur_id'] = null;
            $data['reference'] = 'RET-' . strtoupper(uniqid());
            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);
            return $this->respondCreated($transaction, 'Retrait effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/transfert",
     *     tags={"Transactions"},
     *     summary="Effectuer un transfert entre comptes",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant","compte_emetteur_id","compte_recepteur_id"},
     *             @OA\Property(property="montant", type="number", format="float", example=30000),
     *             @OA\Property(property="compte_emetteur_id", type="string", example="uuid-compte-emetteur"),
     *             @OA\Property(property="compte_recepteur_id", type="string", example="uuid-compte-recepteur"),
     *             @OA\Property(property="note", type="string", example="Transfert d'argent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=400, description="Solde insuffisant")
     * )
     */
    public function transfert(Request $request)
    {
        try {
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'compte_emetteur_id' => 'required|exists:comptes,id',
                'compte_recepteur_id' => 'required|exists:comptes,id|different:compte_emetteur_id',
                'note' => 'nullable|string',
            ]);

            // Vérifier le solde du compte émetteur
            $compteEmetteur = Compte::find($data['compte_emetteur_id']);
            if ($compteEmetteur->solde < $data['montant']) {
                return $this->errorResponse('Solde insuffisant pour effectuer ce transfert', 400);
            }

            $data['type'] = 'transfert';
            $data['reference'] = 'TRF-' . strtoupper(uniqid());
            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);
            return $this->respondCreated($transaction, 'Transfert effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/paiement",
     *     tags={"Transactions"},
     *     summary="Effectuer un paiement (client vers commerçant)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant","compte_emetteur_id","compte_recepteur_id"},
     *             @OA\Property(property="montant", type="number", format="float", example=15000),
     *             @OA\Property(property="compte_emetteur_id", type="string", example="uuid-compte-client"),
     *             @OA\Property(property="compte_recepteur_id", type="string", example="uuid-compte-commercant"),
     *             @OA\Property(property="note", type="string", example="Paiement de produit")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=400, description="Solde insuffisant")
     * )
     */
    public function paiement(Request $request)
    {
        try {
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'compte_emetteur_id' => 'required|exists:comptes,id',
                'compte_recepteur_id' => 'required|exists:comptes,id|different:compte_emetteur_id',
                'note' => 'nullable|string',
            ]);

            // Vérifier le solde du compte émetteur
            $compteEmetteur = Compte::find($data['compte_emetteur_id']);
            if ($compteEmetteur->solde < $data['montant']) {
                return $this->errorResponse('Solde insuffisant pour effectuer ce paiement', 400);
            }

            $data['type'] = 'paiement';
            $data['reference'] = 'PAY-' . strtoupper(uniqid());
            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);
            return $this->respondCreated($transaction, 'Paiement effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
