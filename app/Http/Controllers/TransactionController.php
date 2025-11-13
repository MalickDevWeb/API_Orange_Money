<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Models\User;
use App\Services\TransactionService;
use App\Interfaces\Notifications\BrevoServiceInterface;
use App\Traits\ApiResponseTrait;
use App\Traits\PaginatedSortedTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Système de transactions unifiées - Détection automatique du type selon l'émetteur et le destinataire (Admin→Fournisseur=Dépôt, Fournisseur→Client=Dépôt, Client→Client=Transfert, Client→Commerçant=Paiement)"
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="uuid-transaction"),
 *     @OA\Property(property="type", type="string", enum={"depot","retrait","transfert","paiement","achat_virtuel"}, example="transfert"),
 *     @OA\Property(property="montant", type="number", format="float", example=50000, description="Montant de base de la transaction"),
 *     @OA\Property(property="frais", type="number", format="float", example=7.5, description="Frais totaux appliqués (frais système + taxes utilisateur)"),
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
 * @OA\Schema(
 *     schema="TransactionValidation",
 *     type="object",
 *     description="Règles de validation appliquées automatiquement lors des transactions",
 *     @OA\Property(property="user_not_banned", type="boolean", example=true, description="L'utilisateur ne doit pas être banni"),
 *     @OA\Property(property="transfer_enabled", type="boolean", example=true, description="Les transferts doivent être autorisés"),
 *     @OA\Property(property="specific_permissions", type="string", enum={"can_transfer_to_client","can_pay_merchant"}, description="Permissions spécifiques selon le type de transaction"),
 *     @OA\Property(property="sufficient_balance", type="boolean", example=true, description="Solde suffisant incluant frais et taxes"),
 *     @OA\Property(property="user_tax_applied", type="number", format="float", example=2.5, description="Taxe utilisateur appliquée en %")
 * )
 *
 * @OA\PathItem(
 *     path="/transactions"
 * )
 * @OA\PathItem(
 *     path="/transactions/{transaction}"
 * )
 * @OA\PathItem(
 *     path="/transactions/retrait"
 * )
 * @OA\PathItem(
 *     path="/transactions/unified"
 * )
 *
 * @property-read \App\Models\User $user
 * @method bool canTransfer() on User model
 * @method bool canTransferToClient() on User model
 * @method bool canPayMerchant() on User model
 * @method bool isBanned() on User model
 */
class TransactionController extends Controller
{
    use ApiResponseTrait, PaginatedSortedTrait;

    protected TransactionService $transactionService;
    protected BrevoServiceInterface $brevoService;

    public function __construct(TransactionService $transactionService, BrevoServiceInterface $brevoService)
    {
        $this->transactionService = $transactionService;
        $this->brevoService = $brevoService;
    }

    protected function getAllowedSortFields()
    {
        return ['created_at', 'updated_at', 'montant', 'type', 'statut', 'date_transaction', 'reference', 'frais'];
    }

    /**
     * @OA\Get(
     *     path="/transactions",
     *     tags={"Transactions"},
     *     summary="Lister toutes les transactions avec filtrage avancé",
     *     description="Liste paginée des transactions avec possibilité de filtrer par type, statut, montant, référence, dates et recherche dans les notes. Tri possible par tous les champs principaux.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot","retrait","transfert","paiement","achat_virtuel"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"reussie","echouee","annulee"})
     *     ),
     *     @OA\Parameter(
     *         name="montant_min",
     *         in="query",
     *         description="Montant minimum",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="montant_max",
     *         in="query",
     *         description="Montant maximum",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         description="Rechercher par référence",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche dans les notes et références",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at","updated_at","montant","type","statut","date_transaction","reference","frais"})
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
     *         description="Liste des transactions récupérée avec filtrage et pagination",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="type", type="string", enum={"depot","retrait","transfert","paiement","achat_virtuel"}, example="transfert"),
     *                         @OA\Property(property="montant", type="number", format="float", example=50000),
     *                         @OA\Property(property="frais", type="number", format="float", example=7.5),
     *                         @OA\Property(property="reference", type="string", example="TXN-123456"),
     *                         @OA\Property(property="statut", type="string", enum={"reussie","echouee","annulee"}, example="reussie"),
     *                         @OA\Property(property="note", type="string", example="Paiement de facture"),
     *                         @OA\Property(property="numero_envoyer", type="string", nullable=true, example="771234567"),
     *                         @OA\Property(property="numero_recepteur", type="string", nullable=true, example="771234568")
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Transaction::with(['compteEmetteur.utilisateur', 'compteRecepteur.utilisateur']);

            /** @var \App\Models\User $user */
            $user = auth()->user();
            if (!$user->isAdmin()) {
                // Filter by user's comptes
                $userComptes = $user->comptes->pluck('id');
                $query->where(function($q) use ($userComptes) {
                    $q->whereIn('compte_emetteur_id', $userComptes)
                      ->orWhereIn('compte_recepteur_id', $userComptes);
                });
            }

            // Appliquer les filtres
            $this->applyFilters($query, $request);

            $transactions = $this->getPaginatedSorted($query, $request);

            // Formater les données de sortie
            $formattedData = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'frais' => $transaction->frais ?? 0,
                    'reference' => $transaction->reference,
                    'statut' => $transaction->statut,
                    'note' => $transaction->note,
                    'numero_envoyer' => $transaction->compte_emetteur_id ? $transaction->compteEmetteur->utilisateur->telephone : null,
                    'numero_recepteur' => $transaction->compte_recepteur_id ? $transaction->compteRecepteur->utilisateur->telephone : null,
                ];
            });

            // Remplacer la collection dans l'objet paginé
            $transactions->setCollection($formattedData);

            return $this->successResponse($transactions, 'Transactions récupérées avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Applique les filtres avancés à la requête
     */
    protected function applyFilters($query, Request $request)
    {
        // Filtre par type
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filtre par statut
        if ($request->has('statut') && !empty($request->statut)) {
            $query->where('statut', $request->statut);
        }

        // Filtre par montant (plage)
        if ($request->has('montant_min') && is_numeric($request->montant_min)) {
            $query->where('montant', '>=', $request->montant_min);
        }

        if ($request->has('montant_max') && is_numeric($request->montant_max)) {
            $query->where('montant', '<=', $request->montant_max);
        }

        // Filtre par dates
        if ($request->has('date_debut') && !empty($request->date_debut)) {
            $query->whereDate('date_transaction', '>=', $request->date_debut);
        }

        if ($request->has('date_fin') && !empty($request->date_fin)) {
            $query->whereDate('date_transaction', '<=', $request->date_fin);
        }

        // Filtre par référence exacte
        if ($request->has('reference') && !empty($request->reference)) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }

        // Recherche globale dans notes et référence
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('reference', 'like', '%' . $searchTerm . '%')
                  ->orWhere('note', 'like', '%' . $searchTerm . '%');
            });
        }
    }

    /**
      * @OA\Post(
      *     path="/transactions/depot",
      *     tags={"Transactions"},
      *     summary="Effectuer un dépôt sur le compte d'un client",
      *     description="Permet aux administrateurs et fournisseurs d'effectuer des dépôts sur les comptes clients.",
      *     security={{"bearerAuth":{}}},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"montant","telephone_recepteur_id"},
      *             @OA\Property(property="montant", type="number", format="float", example=50000, description="Montant du dépôt"),
      *             @OA\Property(property="telephone_recepteur_id", type="string", example="771234567", description="Numéro de téléphone du destinataire"),
      *             @OA\Property(property="note", type="string", example="Dépôt client")
      *         )
      *     ),
      *     @OA\Response(
      *         response=201,
      *         description="Dépôt effectué avec succès",
      *         @OA\JsonContent(ref="#/components/schemas/Transaction")
      *     ),
      *     @OA\Response(response=403, description="Accès non autorisé"),
      *     @OA\Response(response=404, description="Utilisateur destinataire non trouvé")
      * )
      */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'type' => 'required|string|in:depot,retrait,transfert,paiement,achat_virtuel',
                'montant' => 'required|numeric|min:0.01',
                'reference' => 'nullable|string|unique:transactions,reference',
                'note' => 'nullable|string',
                'telephone_emetteur_id' => 'nullable|string|exists:users,telephone',
                'telephone_recepteur_id' => 'nullable|string|exists:users,telephone',
            ]);

            // Validation spécifique selon le type
            switch ($data['type']) {
                case 'depot':
                    $request->validate([
                        'telephone_recepteur_id' => 'required|string|exists:users,telephone',
                    ]);
                    // Trouver le compte récepteur
                    $userRecepteur = \App\Models\User::where('telephone', $data['telephone_recepteur_id'])->first();
                    if (!$userRecepteur || !$userRecepteur->comptes->first()) {
                        return $this->errorResponse('Utilisateur destinataire non trouvé ou sans compte', 404);
                    }
                    $data['compte_emetteur_id'] = null; // Système
                    $data['compte_recepteur_id'] = $userRecepteur->comptes->first()->id;
                    $data['reference'] = $data['reference'] ?? 'DEP-' . strtoupper(uniqid());
                    break;

                case 'retrait':
                    $request->validate([
                        'telephone_emetteur_id' => 'required|string|exists:users,telephone',
                    ]);
                    // Trouver le compte émetteur
                    /** @var \App\Models\User $userEmetteur */
                    $userEmetteur = User::where('telephone', $data['telephone_emetteur_id'])->first();
                    if (!$userEmetteur || !$userEmetteur->comptes->first()) {
                        return $this->errorResponse('Utilisateur émetteur non trouvé ou sans compte', 404);
                    }

                    // Vérifier les droits de l'utilisateur émetteur
                    /** @phpstan-ignore-next-line */
                    if ($userEmetteur->isBanned()) {
                        return $this->errorResponse('Votre compte est banni. Retrait impossible.', 403);
                    }
                    /** @phpstan-ignore-next-line */
                    if (!$userEmetteur->canTransfer()) {
                        return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des retraits.', 403);
                    }

                    $compte = $userEmetteur->comptes->first();
                    if ($compte->solde < $data['montant']) {
                        return $this->errorResponse('Solde insuffisant pour effectuer ce retrait', 400);
                    }
                    $data['compte_emetteur_id'] = $compte->id;
                    $data['compte_recepteur_id'] = null; // Système
                    $data['reference'] = $data['reference'] ?? 'RET-' . strtoupper(uniqid());
                    break;

                case 'transfert':
                    $request->validate([
                        'telephone_emetteur_id' => 'required|string|exists:users,telephone',
                        'telephone_recepteur_id' => 'required|string|exists:users,telephone|different:telephone_emetteur_id',
                    ]);
                    // Trouver les comptes
                    /** @var \App\Models\User $userEmetteur */
                    $userEmetteur = \App\Models\User::where('telephone', $data['telephone_emetteur_id'])->first();
                    /** @var \App\Models\User $userRecepteur */
                    $userRecepteur = \App\Models\User::where('telephone', $data['telephone_recepteur_id'])->first();
                    if (!$userEmetteur || !$userEmetteur->comptes->first() || !$userRecepteur || !$userRecepteur->comptes->first()) {
                        return $this->errorResponse('Utilisateurs non trouvés ou sans comptes', 404);
                    }

                    // Vérifier les droits de l'utilisateur émetteur
                    /** @phpstan-ignore-next-line */
                    if ($userEmetteur->isBanned()) {
                        return $this->errorResponse('Votre compte est banni. Transfert impossible.', 403);
                    }
                    /** @phpstan-ignore-next-line */
                    if (!$userEmetteur->canTransfer()) {
                        return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts.', 403);
                    }
                    /** @phpstan-ignore-next-line */
                    if (!$userEmetteur->canTransferToClient()) {
                        return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts vers d\'autres clients.', 403);
                    }

                    $compteEmetteur = $userEmetteur->comptes->first();
                    if ($compteEmetteur->solde < $data['montant']) {
                        return $this->errorResponse('Solde insuffisant pour effectuer ce transfert', 400);
                    }
                    $data['compte_emetteur_id'] = $compteEmetteur->id;
                    $data['compte_recepteur_id'] = $userRecepteur->comptes->first()->id;
                    $data['reference'] = $data['reference'] ?? 'TRF-' . strtoupper(uniqid());
                    break;

                case 'paiement':
                    $request->validate([
                        'code_marchand' => 'nullable|string|exists:comptes,code_marchand',
                        'telephone_marchand' => 'nullable|string|exists:users,telephone',
                    ]);

                    // Vérifier qu'au moins un des deux est fourni
                    if (empty($request->code_marchand) && empty($request->telephone_marchand)) {
                        return $this->errorResponse('Vous devez fournir soit le code marchand soit le numéro de téléphone du marchand', 422);
                    }
                    if (!empty($request->code_marchand) && !empty($request->telephone_marchand)) {
                        return $this->errorResponse('Vous ne pouvez pas fournir à la fois le code marchand et le numéro de téléphone du marchand', 422);
                    }

                    // L'émetteur est l'utilisateur connecté
                    /** @var \App\Models\User $userEmetteur */
                    $userEmetteur = auth()->user();

                    // Vérifier les droits de l'utilisateur émetteur
                    /** @phpstan-ignore-next-line */
                    if ($userEmetteur->isBanned()) {
                        return $this->errorResponse('Votre compte est banni. Paiement impossible.', 403);
                    }
                    /** @phpstan-ignore-next-line */
                    if (!$userEmetteur->canTransfer()) {
                        return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements.', 403);
                    }
                    /** @phpstan-ignore-next-line */
                    if (!$userEmetteur->canPayMerchant()) {
                        return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements vers les marchands.', 403);
                    }

                    $compteEmetteur = $userEmetteur->comptes->first();
                    if (!$compteEmetteur) {
                        return $this->errorResponse('Aucun compte trouvé pour l\'utilisateur connecté', 404);
                    }

                    // Trouver le compte récepteur par code marchand ou numéro de téléphone
                    $frais = 0;
                    if (!empty($request->code_marchand)) {
                        $compteRecepteur = Compte::where('code_marchand', $request->code_marchand)->first();
                        if (!$compteRecepteur) {
                            return $this->errorResponse('Marchand non trouvé', 404);
                        }
                        $userRecepteur = $compteRecepteur->utilisateur;
                        if ($userRecepteur->type !== 'commercant') {
                            return $this->errorResponse('Le compte fourni n\'appartient pas à un marchand', 400);
                        }
                        // Paiement par code marchand : pas de frais
                        $frais = 0;
                    } else {
                        $userRecepteur = \App\Models\User::where('telephone', $request->telephone_marchand)->where('type', 'commercant')->first();
                        if (!$userRecepteur) {
                            return $this->errorResponse('Marchand non trouvé', 404);
                        }
                        $compteRecepteur = $userRecepteur->comptes->first();
                        if (!$compteRecepteur) {
                            return $this->errorResponse('Marchand non trouvé', 404);
                        }
                        // Paiement par numéro de téléphone : frais de 0.5%
                        $frais = $data['montant'] * 0.005;
                    }

                    // Vérifier que les comptes sont différents
                    if ($compteEmetteur->id === $compteRecepteur->id) {
                        return $this->errorResponse('Impossible de payer vers le même compte', 400);
                    }

                    // Appliquer la taxe spécifique de l'utilisateur si définie
                    $taxeUtilisateur = $userEmetteur->tax_percentage > 0 ? $data['montant'] * ($userEmetteur->tax_percentage / 100) : 0;

                    // Calculer le montant total à débiter (montant + frais + taxe)
                    $montantTotal = $data['montant'] + $frais + $taxeUtilisateur;

                    // Vérifier le solde du compte émetteur
                    if ($compteEmetteur->solde < $montantTotal) {
                        return $this->errorResponse('Solde insuffisant pour effectuer ce paiement (incluant les frais et taxes)', 400);
                    }

                    $data['compte_emetteur_id'] = $compteEmetteur->id;
                    $data['compte_recepteur_id'] = $compteRecepteur->id;
                    $data['montant'] = $montantTotal; // Le montant débité inclut les frais et taxes
                    $data['frais'] = $frais + $taxeUtilisateur; // Frais incluant la taxe utilisateur
                    $data['reference'] = $data['reference'] ?? 'PAY-' . strtoupper(uniqid());
                    break;

                case 'achat_virtuel':
                    // Vérifier que l'utilisateur est admin
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    if (!$user->isAdmin()) {
                        return $this->errorResponse('Accès réservé aux administrateurs', 403);
                    }
                    // Trouver le compte admin
                    $compteAdmin = auth()->user()->comptes->first();
                    if (!$compteAdmin) {
                        return $this->errorResponse('Aucun compte trouvé pour l\'administrateur', 404);
                    }
                    $data['compte_emetteur_id'] = null;
                    $data['compte_recepteur_id'] = $compteAdmin->id;
                    $data['reference'] = $data['reference'] ?? 'ACHAT-' . strtoupper(uniqid());
                    break;
            }

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
     *     path="/transactions/{transaction}",
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
     *     path="/transactions/{transaction}",
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
     *     path="/transactions/{transaction}",
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

    public function depot(Request $request)
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = auth()->user();

            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'telephone_recepteur_id' => 'required|string|exists:users,telephone',
                'note' => 'nullable|string',
            ]);


            // Trouver le compte récepteur par numéro de téléphone
            $userRecepteur = \App\Models\User::where('telephone', $data['telephone_recepteur_id'])->first();
            if (!$userRecepteur) {
                return $this->errorResponse('Utilisateur destinataire non trouvé', 404);
            }
            $compteRecepteur = $userRecepteur->comptes->first();
            if (!$compteRecepteur) {
                return $this->errorResponse('Aucun compte trouvé pour l\'utilisateur destinataire', 404);
            }

            $compteEmetteur = $authenticatedUser->comptes->first();
            if (!$compteEmetteur) {
                return $this->errorResponse('Aucun compte trouvé pour le fournisseur', 404);
            }

            // Vérifier le solde du fournisseur
            if ($compteEmetteur->solde < $data['montant']) {
                return $this->errorResponse('Solde insuffisant pour effectuer ce dépôt', 400);
            }

            // Pour un dépôt, l'émetteur est le fournisseur authentifié
            $data['type'] = 'depot';
            $data['compte_emetteur_id'] = $compteEmetteur->id; // Fournisseur
            $data['compte_recepteur_id'] = $compteRecepteur->id;
            $data['reference'] = 'DEP-' . strtoupper(uniqid());
            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);

            // Envoyer un email de confirmation au destinataire
            if ($userRecepteur->email) {
                $subject = "Dépôt reçu - {$transaction->reference}";
                $message = "Vous avez reçu un dépôt de {$transaction->montant} FCFA.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'receiver'
                ])->render();
                $this->brevoService->sendMail($userRecepteur->email, $subject, $htmlContent);
            }

            return $this->respondCreated($transaction, 'Dépôt effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/retrait",
     *     tags={"Transactions"},
     *     summary="Effectuer un retrait d'un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant","telephone_emetteur"},
     *             @OA\Property(property="montant", type="number", format="float", example=25000),
     *             @OA\Property(property="telephone_emetteur", type="string", example="771234567"),
             @OA\Property(property="note", type="string", example="Retrait d'argent")
         )
     ),
     @OA\Response(
         response=201,
         description="Retrait effectué avec succès",
         @OA\JsonContent(ref="#/components/schemas/Transaction")
     ),
     @OA\Response(response=400, description="Solde insuffisant")
 )
 */
    public function retrait(Request $request)
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = auth()->user();

            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'telephone_emetteur' => 'nullable|string|exists:users,telephone',
                'note' => 'nullable|string',
            ]);

            // Déterminer l'émetteur
            if ($authenticatedUser->isFournisseur()) {
                // Fournisseur initie un retrait pour un client
                if (empty($data['telephone_emetteur'])) {
                    return $this->errorResponse('Numéro de téléphone du client requis', 400);
                }
                $telephoneEmetteur = $data['telephone_emetteur'];
            } else {
                // Client retire de son propre compte
                $telephoneEmetteur = $authenticatedUser->telephone;
            }

            // Trouver l'utilisateur émetteur
            $userEmetteur = \App\Models\User::where('telephone', $telephoneEmetteur)->first();
            if (!$userEmetteur) {
                return $this->errorResponse('Utilisateur émetteur non trouvé', 404);
            }
            $compteEmetteur = $userEmetteur->comptes->first();
            if (!$compteEmetteur) {
                return $this->errorResponse('Aucun compte trouvé pour l\'utilisateur émetteur', 404);
            }

            // Vérifier le solde du compte
            if ($compteEmetteur->solde < $data['montant']) {
                // Envoyer des emails d'erreur
                $this->sendInsufficientBalanceEmails($userEmetteur, $authenticatedUser, $data['montant']);
                return $this->errorResponse('Solde insuffisant pour effectuer ce retrait', 400);
            }

            // Préparer les données de transaction
            $transactionData = [
                'type' => 'retrait',
                'montant' => $data['montant'],
                'note' => $data['note'] ?? 'Retrait d\'argent',
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_recepteur_id' => null,
                'reference' => 'RET-' . strtoupper(uniqid()),
                'date_transaction' => now(),
            ];

            if ($authenticatedUser->isFournisseur() && $telephoneEmetteur !== $authenticatedUser->telephone) {
                // Fournisseur initie retrait pour client : transaction en attente, envoyer OTP
                $transactionData['statut'] = 'pending';

                $transaction = $this->transactionService->create($transactionData);

                // Générer et envoyer OTP par email au client
                $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                \App\Models\OtpCode::create([
                    'user_id' => $userEmetteur->id,
                    'phone_number' => $userEmetteur->telephone,
                    'code' => $otpCode,
                    'type' => 'confirm_retrait',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'montant' => $data['montant'],
                        'fournisseur_id' => $authenticatedUser->id,
                    ],
                    'expires_at' => now()->addMinutes(10),
                ]);

                // Envoyer l'OTP par email au client
                if ($userEmetteur->email) {
                    $subject = "Code de confirmation de retrait - {$transaction->reference}";
                    $message = "Voici votre code de retrait : {$otpCode}. Montant : {$data['montant']} FCFA. Ce code expire dans 10 minutes.";
                    $htmlContent = View::make('emails.transaction-notification', [
                        'transaction' => $transaction,
                        'message' => $message,
                        'role' => 'confirmation'
                    ])->render();
                    $this->brevoService->sendMail($userEmetteur->email, $subject, $htmlContent);
                }

                // Envoyer un email au fournisseur
                if ($authenticatedUser->email) {
                    $subject = "Retrait initié - {$transaction->reference}";
                    $message = "Vous avez initié un retrait de {$data['montant']} FCFA pour le client {$userEmetteur->nom} {$userEmetteur->prenom}. En attente de confirmation du client.";
                    $htmlContent = View::make('emails.transaction-notification', [
                        'transaction' => $transaction,
                        'message' => $message,
                        'role' => 'supplier'
                    ])->render();
                    $this->brevoService->sendMail($authenticatedUser->email, $subject, $htmlContent);
                }

                return $this->successResponse([
                    'transaction_id' => $transaction->id,
                    'requires_confirmation' => true,
                ], 'Retrait initié. Code de confirmation envoyé par email au client.', 201);
            } else {
                // Retrait direct (client ou fournisseur pour lui-même)
                $transactionData['statut'] = 'reussie';
                $transaction = $this->transactionService->create($transactionData);
                return $this->respondCreated($transaction, 'Retrait effectué avec succès');
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/confirm-retrait",
     *     tags={"Transactions"},
     *     summary="Confirmer un retrait initié par un fournisseur",
     *     description="Le fournisseur entre le code OTP fourni par le client pour confirmer le retrait.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"confirmation_code"},
     *             @OA\Property(property="confirmation_code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retrait confirmé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=400, description="Code invalide ou expiré"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Transaction non trouvée")
     * )
     */
    public function confirmRetrait(Request $request)
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = auth()->user();

            $data = $request->validate([
                'confirmation_code' => 'required|string|size:6',
            ]);

            // Trouver l'OTP valide pour ce fournisseur
            $otpCode = \App\Models\OtpCode::where('code', $data['confirmation_code'])
                                          ->where('type', 'confirm_retrait')
                                          ->whereJsonContains('data->fournisseur_id', $authenticatedUser->id)
                                          ->first();

            if (!$otpCode || $otpCode->isExpired()) {
                return $this->errorResponse('Code de confirmation invalide ou expiré', 400);
            }

            // Récupérer l'ID de la transaction depuis les données OTP
            $transactionId = $otpCode->data['transaction_id'];

            // Trouver la transaction
            $transaction = \App\Models\Transaction::find($transactionId);
            if (!$transaction || $transaction->type !== 'retrait' || $transaction->statut !== 'pending') {
                return $this->errorResponse('Transaction non trouvée ou déjà confirmée', 404);
            }

            // Marquer l'OTP comme utilisé
            $otpCode->markAsUsed();

            // Confirmer la transaction
            $transaction->update(['statut' => 'reussie']);

            // Envoyer les emails de confirmation
            $this->sendRetraitConfirmationEmails($transaction, $authenticatedUser);

            return $this->successResponse($transaction, 'Retrait confirmé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    protected function sendTransactionErrorEmails(User $sender, User $receiver, float $montant, string $errorMessage): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('Envoi d\'emails d\'erreur de transaction', [
                'sender_email' => $sender->email,
                'receiver_email' => $receiver->email,
                'montant' => $montant,
                'error' => $errorMessage
            ]);

            // Email à l'émetteur
            if ($sender->email) {
                $subject = "Échec de transaction - {$errorMessage}";
                $message = "Votre tentative de transaction de {$montant} FCFA a échoué : {$errorMessage}. Veuillez vérifier vos informations et réessayer.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'error'
                ])->render();
                $result = $this->brevoService->sendMail($sender->email, $subject, $htmlContent);
                \Illuminate\Support\Facades\Log::info('Email d\'erreur envoyé à l\'émetteur', ['email' => $sender->email, 'result' => $result]);
            }

            // Email au destinataire
            if ($receiver->email) {
                $subject = "Notification d'échec de transaction";
                $message = "Une transaction de {$montant} FCFA initiée vers vous a échoué : {$errorMessage}.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'error'
                ])->render();
                $result = $this->brevoService->sendMail($receiver->email, $subject, $htmlContent);
                \Illuminate\Support\Facades\Log::info('Email d\'erreur envoyé au destinataire', ['email' => $receiver->email, 'result' => $result]);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas interrompre la réponse
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi d\'emails d\'erreur de transaction: ' . $e->getMessage());
        }
    }

    protected function sendTransactionSuccessEmails(User $sender, User $receiver, $transaction, string $typeTransaction): void
    {
        try {
            // Email au destinataire (client)
            if ($receiver->email) {
                $newBalance = $receiver->comptes->first()->solde ?? 0;
                $subject = "Transaction reçue - {$transaction->reference}";
                $message = "Vous avez reçu une transaction de {$transaction->montant} FCFA de {$sender->nom} {$sender->prenom}. Nouveau solde : {$newBalance} FCFA.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'receiver'
                ])->render();
                $this->brevoService->sendMail($receiver->email, $subject, $htmlContent);
            }

            // Email au fournisseur (émetteur)
            if ($sender->email) {
                $currentBalance = $sender->comptes->first()->solde ?? 0;
                $subject = "Transaction effectuée - {$transaction->reference}";
                $message = "Votre transaction de {$transaction->montant} FCFA vers {$receiver->nom} {$receiver->prenom} a été effectuée avec succès. Solde actuel : {$currentBalance} FCFA.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'sender'
                ])->render();
                $this->brevoService->sendMail($sender->email, $subject, $htmlContent);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas interrompre la réponse
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi d\'emails de succès de transaction: ' . $e->getMessage());
        }
    }

    protected function sendInsufficientBalanceEmails(User $client, User $fournisseur, float $montant): void
    {
        $this->sendTransactionErrorEmails($client, $fournisseur, $montant, 'Solde insuffisant');
    }

    protected function sendRetraitConfirmationEmails(Transaction $transaction, User $fournisseur): void
    {
        try {
            // Email au client (émetteur)
            if ($transaction->compteEmetteur && $transaction->compteEmetteur->utilisateur) {
                $client = $transaction->compteEmetteur->utilisateur;
                if ($client->email) {
                    $soldeActuel = $transaction->compteEmetteur->solde;
                    $subject = "Confirmation de retrait - {$transaction->reference}";
                    $message = "Votre retrait de {$transaction->montant} FCFA a été confirmé avec succès. Solde actuel : {$soldeActuel} FCFA.";
                    $htmlContent = View::make('emails.transaction-notification', [
                        'transaction' => $transaction,
                        'message' => $message,
                        'role' => 'sender'
                    ])->render();
                    $this->brevoService->sendMail($client->email, $subject, $htmlContent);
                }
            }

            // Email au fournisseur
            if ($fournisseur->email) {
                $subject = "Confirmation de retrait effectué - {$transaction->reference}";
                $message = "Le retrait de {$transaction->montant} FCFA pour le client {$transaction->compteEmetteur->utilisateur->nom} {$transaction->compteEmetteur->utilisateur->prenom} a été confirmé avec succès.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'supplier'
                ])->render();
                $this->brevoService->sendMail($fournisseur->email, $subject, $htmlContent);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas interrompre la réponse
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi d\'emails de confirmation de retrait: ' . $e->getMessage());
        }
    }


    public function transfert(Request $request)
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = auth()->user();

            // Validation de base
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'telephone_recepteur_id' => 'required|string|exists:users,telephone',
                'note' => 'nullable|string',
            ]);

            // Utiliser l'utilisateur connecté comme émetteur
            $userEmetteur = $authenticatedUser;
            $data['telephone_emetteur_id'] = $authenticatedUser->telephone;

            // Vérifier que l'émetteur et le récepteur sont différents
            if ($data['telephone_emetteur_id'] === $data['telephone_recepteur_id']) {
                return $this->errorResponse('Impossible de transférer vers le même numéro', 400);
            }

            // Trouver l'utilisateur récepteur
            $userRecepteur = \App\Models\User::where('telephone', $data['telephone_recepteur_id'])->first();
            if (!$userRecepteur) {
                return $this->errorResponse('Destinataire non trouvé', 404);
            }

            // Déterminer automatiquement le type de transaction selon la matrice :
            // Admin/Fournisseur → Client/Fournisseur = dépôt
            // Client → Client = transfert
            // Client → Commerçant = paiement

            $typeTransaction = 'transfert'; // par défaut

            if ($userEmetteur->isAdmin() || $userEmetteur->isFournisseur()) {
                // Admin ou Fournisseur vers n'importe qui = dépôt
                $typeTransaction = 'depot';
            } elseif ($userEmetteur->isClient() && $userRecepteur->isCommercant()) {
                // Client vers Commerçant = paiement
                $typeTransaction = 'paiement';
            } elseif ($userEmetteur->isClient() && $userRecepteur->isClient()) {
                // Client vers Client = transfert
                $typeTransaction = 'transfert';
            }

            // Trouver les comptes
            $compteEmetteur = $userEmetteur->comptes->first();
            if (!$compteEmetteur) {
                return $this->errorResponse('Aucun compte trouvé pour l\'émetteur', 404);
            }

            $compteRecepteur = $userRecepteur->comptes->first();
            if (!$compteRecepteur) {
                return $this->errorResponse('Aucun compte trouvé pour le destinataire', 404);
            }

            // Vérifications de sécurité selon le type de transaction
            /** @phpstan-ignore-next-line */
            if ($userEmetteur->isBanned()) {
                return $this->errorResponse('Votre compte est banni. Transaction impossible.', 403);
            }

            // Calcul des frais selon le type
            $frais = 0;
            $taxeUtilisateur = $userEmetteur->tax_percentage > 0 ? $data['montant'] * ($userEmetteur->tax_percentage / 100) : 0;

            if ($typeTransaction === 'paiement') {
                // Vérifier les droits de paiement marchand
                /** @phpstan-ignore-next-line */
                if (!$userEmetteur->canTransfer()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements.', 403);
                }
                /** @phpstan-ignore-next-line */
                if (!$userEmetteur->canPayMerchant()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements vers les marchands.', 403);
                }

                // Pour paiement : pas de frais supplémentaires (déjà inclus dans la logique métier)
                $frais = 0;
            } elseif ($typeTransaction === 'transfert') {
                // Vérifier les droits de transfert
                /** @phpstan-ignore-next-line */
                if (!$userEmetteur->canTransfer()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts.', 403);
                }
                /** @phpstan-ignore-next-line */
                if (!$userEmetteur->canTransferToClient()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts vers d\'autres clients.', 403);
                }
            } elseif ($typeTransaction === 'depot') {
                // Pour dépôt (admin/fournisseur) : pas de vérification de droits supplémentaire
            }

            // Calcul du montant total selon le type
            if ($typeTransaction === 'paiement') {
                $montantTotal = $data['montant'] + $frais + $taxeUtilisateur;
            } else {
                $montantTotal = $data['montant'];
            }

            // Vérifier le solde
            if ($compteEmetteur->solde < $montantTotal) {
                return $this->errorResponse('Solde insuffisant pour effectuer cette transaction', 400);
            }

            // Vérifier que les comptes sont différents
            if ($compteEmetteur->id === $compteRecepteur->id) {
                return $this->errorResponse('Impossible de transférer vers le même compte', 400);
            }

            // Préparer les données de transaction
            $data['type'] = $typeTransaction;
            $data['compte_emetteur_id'] = $compteEmetteur->id;
            $data['compte_recepteur_id'] = $compteRecepteur->id;

            if ($typeTransaction === 'depot') {
                $data['reference'] = 'DEP-' . strtoupper(uniqid());
            } elseif ($typeTransaction === 'transfert') {
                $data['reference'] = 'TRF-' . strtoupper(uniqid());
            } elseif ($typeTransaction === 'paiement') {
                $data['reference'] = 'PAY-' . strtoupper(uniqid());
                $data['frais'] = $frais + $taxeUtilisateur;
                $data['montant'] = $montantTotal; // Montant débité inclut frais et taxes
            }

            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();

            $transaction = $this->transactionService->create($data);

            $message = match($typeTransaction) {
                'depot' => 'Dépôt effectué avec succès',
                'transfert' => 'Transfert effectué avec succès',
                'paiement' => 'Paiement effectué avec succès',
                default => 'Transaction effectuée avec succès'
            };

            return $this->respondCreated($transaction, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function paiement(Request $request)
    {
        try {
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'code_marchand' => 'nullable|string|exists:comptes,code_marchand',
                'telephone_marchand' => 'nullable|string|exists:users,telephone',
                'note' => 'nullable|string',
            ]);

            // Vérifier qu'au moins un des deux est fourni
            if (empty($data['code_marchand']) && empty($data['telephone_marchand'])) {
                return $this->errorResponse('Vous devez fournir soit le code marchand soit le numéro de téléphone du marchand', 422);
            }
            if (!empty($data['code_marchand']) && !empty($data['telephone_marchand'])) {
                return $this->errorResponse('Vous ne pouvez pas fournir à la fois le code marchand et le numéro de téléphone du marchand', 422);
            }

            // L'émetteur est l'utilisateur connecté
            $userEmetteur = auth()->user();
            $compteEmetteur = $userEmetteur->comptes->first();
            if (!$compteEmetteur) {
                return $this->errorResponse('Aucun compte trouvé pour l\'utilisateur connecté', 404);
            }

            // Trouver le compte récepteur par code marchand ou numéro de téléphone
            $frais = 0;
            if (!empty($data['code_marchand'])) {
                $compteRecepteur = Compte::where('code_marchand', $data['code_marchand'])->first();
                if (!$compteRecepteur) {
                    return $this->errorResponse('Marchand non trouvé', 404);
                }
                $userRecepteur = $compteRecepteur->utilisateur;
                if ($userRecepteur->type !== 'commercant') {
                    return $this->errorResponse('Le compte fourni n\'appartient pas à un marchand', 400);
                }
                // Paiement par code marchand : pas de frais
                $frais = 0;
            } else {
                $userRecepteur = \App\Models\User::where('telephone', $data['telephone_marchand'])->where('type', 'commercant')->first();
                if (!$userRecepteur) {
                    return $this->errorResponse('Marchand non trouvé', 404);
                }
                $compteRecepteur = $userRecepteur->comptes->first();
                if (!$compteRecepteur) {
                    return $this->errorResponse('Marchand non trouvé', 404);
                }
                // Paiement par numéro de téléphone : frais de 0.5%
                $frais = $data['montant'] * 0.005;
            }

            // Vérifier que les comptes sont différents
            if ($compteEmetteur->id === $compteRecepteur->id) {
                return $this->errorResponse('Impossible de payer vers le même compte', 400);
            }

            // Calculer le montant total à débiter (montant + frais)
            $montantTotal = $data['montant'] + $frais;

            // Vérifier le solde du compte émetteur
            if ($compteEmetteur->solde < $montantTotal) {
                return $this->errorResponse('Solde insuffisant pour effectuer ce paiement (incluant les frais)', 400);
            }

            $data['type'] = 'paiement';
            $data['montant'] = $montantTotal; // Le montant débité inclut les frais
            $data['compte_emetteur_id'] = $compteEmetteur->id;
            $data['compte_recepteur_id'] = $compteRecepteur->id;
            $data['reference'] = 'PAY-' . strtoupper(uniqid());
            $data['statut'] = 'reussie';
            $data['date_transaction'] = now();
            $data['frais'] = $frais; // Ajouter les frais dans les données

            $transaction = $this->transactionService->create($data);
            return $this->respondCreated($transaction, 'Paiement effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/achat-virtuel",
     *     tags={"Transactions"},
     *     summary="Achat d'argent virtuel par l'admin",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant"},
             @OA\Property(property="montant", type="number", format="float", example=50000),
             @OA\Property(property="note", type="string", example="Achat d'argent virtuel")
         )
     ),
     @OA\Response(
         response=201,
         description="Achat virtuel effectué avec succès",
         @OA\JsonContent(ref="#/components/schemas/Transaction")
     ),
     @OA\Response(response=403, description="Accès réservé aux admins")
 )
 */
    public function achatVirtuel(Request $request)
    {
        try {
            // Vérifier que l'utilisateur est admin
            /** @var \App\Models\User $adminUser */
            $adminUser = auth()->user();
            if (!$adminUser->isAdmin()) {
                return $this->errorResponse('Accès réservé aux administrateurs', 403);
            }

            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'note' => 'nullable|string',
            ]);

            // Trouver le compte admin
            $compteAdmin = $adminUser->comptes->first();
            if (!$compteAdmin) {
                return $this->errorResponse('Aucun compte trouvé pour l\'administrateur', 404);
            }

            // Créer la transaction d'achat virtuel (enregistrement)
            $transactionData = [
                'type' => 'achat_virtuel',
                'montant' => $data['montant'],
                'reference' => 'ACHAT-' . strtoupper(uniqid()),
                'note' => $data['note'] ?? 'Achat d\'argent virtuel',
                'compte_emetteur_id' => null, // Système
                'compte_recepteur_id' => $compteAdmin->id,
                'statut' => 'reussie',
                'date_transaction' => now(),
            ];

            $transaction = $this->transactionService->create($transactionData);
            return $this->successResponse($transaction, 'Achat virtuel effectué avec succès', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
      * @OA\Post(
      *     path="/transactions/unified",
      *     tags={"Transactions"},
      *     summary="Transaction unifiée intelligente - Détection automatique du type selon l'émetteur et le destinataire",
      *     description="Endpoint unique qui détecte automatiquement le type de transaction selon la matrice : Admin→Fournisseur=Dépôt, Fournisseur→Client=Dépôt, Client→Client=Transfert, Client→Commerçant=Paiement. L'émetteur est toujours l'utilisateur connecté. La note est générée automatiquement.",
      *     security={{"bearerAuth":{}}},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"montant","telephone_recepteur"},
      *             @OA\Property(property="montant", type="number", format="float", example=50000, description="Montant de la transaction"),
      *             @OA\Property(property="telephone_recepteur", type="string", example="771234567", description="Numéro de téléphone du destinataire")
      *         )
      *     ),
      *     @OA\Response(
      *         response=201,
      *         description="Transaction effectuée avec succès",
      *         @OA\JsonContent(ref="#/components/schemas/Transaction")
      *     ),
      *     @OA\Response(response=400, description="Solde insuffisant ou comptes invalides"),
      *     @OA\Response(response=403, description="Droits insuffisants"),
      *     @OA\Response(response=404, description="Utilisateur non trouvé")
      * )
      */
    public function unifiedTransaction(Request $request)
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = auth()->user();

            // Validation de base
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'telephone_recepteur' => 'required|string|exists:users,telephone',
            ]);


            // L'émetteur est toujours l'utilisateur connecté
            $telephoneEmetteur = $authenticatedUser->telephone;

            // Vérifier que l'émetteur et le récepteur sont différents
            if ($telephoneEmetteur === $data['telephone_recepteur']) {
                return $this->errorResponse('Impossible de faire une transaction vers le même numéro', 400);
            }

            // Trouver les utilisateurs
            $userEmetteur = $authenticatedUser;
            $userRecepteur = \App\Models\User::where('telephone', $data['telephone_recepteur'])->first();
            if (!$userRecepteur) {
                return $this->errorResponse('Destinataire non trouvé', 404);
            }

            // Déterminer automatiquement le type de transaction selon la matrice :
            // Admin → Fournisseur = dépôt
            // Fournisseur → Client = dépôt
            // Client → Client = transfert
            // Client → Commerçant = paiement
            $typeTransaction = 'transfert'; // par défaut

            if ($userEmetteur->isAdmin() && $userRecepteur->isFournisseur()) {
                $typeTransaction = 'depot';
            } elseif ($userEmetteur->isFournisseur() && $userRecepteur->isClient()) {
                $typeTransaction = 'depot';
            } elseif ($userEmetteur->isClient() && $userRecepteur->isClient()) {
                $typeTransaction = 'transfert';
            } elseif ($userEmetteur->isClient() && $userRecepteur->isCommercant()) {
                $typeTransaction = 'paiement';
            }

            // Générer la note automatiquement
            $note = match($typeTransaction) {
                'depot' => 'Dépôt vers ' . $data['telephone_recepteur'],
                'transfert' => 'Transfert vers ' . $data['telephone_recepteur'],
                'paiement' => 'Paiement vers ' . $data['telephone_recepteur'],
                default => 'Transaction unifiée'
            };

            // Trouver les comptes
            $compteEmetteur = $userEmetteur->comptes->first();
            if (!$compteEmetteur) {
                return $this->errorResponse('Aucun compte trouvé pour l\'émetteur', 404);
            }

            $compteRecepteur = $userRecepteur->comptes->first();
            if (!$compteRecepteur) {
                return $this->errorResponse('Aucun compte trouvé pour le destinataire', 404);
            }

            // Vérifications de sécurité selon le type de transaction
            if ($userEmetteur->isBanned()) {
                return $this->errorResponse('Votre compte est banni. Transaction impossible.', 403);
            }

            // Calcul des frais selon le type
            $frais = 0;
            $taxeUtilisateur = $userEmetteur->tax_percentage > 0 ? $data['montant'] * ($userEmetteur->tax_percentage / 100) : 0;

            if ($typeTransaction === 'paiement') {
                // Vérifier les droits de paiement marchand
                if (!$userEmetteur->canTransfer()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements.', 403);
                }
                if (!$userEmetteur->canPayMerchant()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements vers les marchands.', 403);
                }
                $frais = 0; // Les frais sont déjà gérés dans la logique métier
            } elseif ($typeTransaction === 'transfert') {
                // Vérifier les droits de transfert
                if (!$userEmetteur->canTransfer()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts.', 403);
                }
                if (!$userEmetteur->canTransferToClient()) {
                    return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts vers d\'autres clients.', 403);
                }
            } elseif ($typeTransaction === 'depot') {
                // Pour dépôt (admin/fournisseur) : pas de vérification de droits supplémentaire
            }

            // Calcul du montant total selon le type
            if ($typeTransaction === 'paiement') {
                $montantTotal = $data['montant'] + $frais + $taxeUtilisateur;
            } else {
                $montantTotal = $data['montant'];
            }

            // Vérifier le solde pour les transactions qui débitent un compte
            if ($compteEmetteur->solde < $montantTotal) {
                // Envoyer des emails d'erreur aux deux parties
                $this->sendTransactionErrorEmails($userEmetteur, $userRecepteur, $data['montant'], 'Solde insuffisant pour effectuer cette transaction');
                return $this->errorResponse('Solde insuffisant pour effectuer cette transaction', 400);
            }

            // Vérifier que les comptes sont différents
            if ($compteEmetteur->id === $compteRecepteur->id) {
                return $this->errorResponse('Impossible de faire une transaction vers le même compte', 400);
            }

            // Préparer les données de transaction
            $transactionData = [
                'type' => $typeTransaction,
                'montant' => $data['montant'],
                'note' => $note,
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_recepteur_id' => $compteRecepteur->id,
                'statut' => 'reussie',
                'date_transaction' => now(),
            ];

            // Ajustements selon le type
            if ($typeTransaction === 'depot') {
                $transactionData['reference'] = 'DEP-' . strtoupper(uniqid());
                // Pour dépôt fournisseur->client, l'émetteur est le fournisseur
            } elseif ($typeTransaction === 'transfert') {
                $transactionData['reference'] = 'TRF-' . strtoupper(uniqid());
            } elseif ($typeTransaction === 'paiement') {
                $transactionData['reference'] = 'PAY-' . strtoupper(uniqid());
                $transactionData['frais'] = $frais + $taxeUtilisateur;
                $transactionData['montant'] = $montantTotal; // Montant débité inclut frais et taxes
            }

            $transaction = $this->transactionService->create($transactionData);

            // Envoyer des emails de confirmation aux deux parties
            $this->sendTransactionSuccessEmails($userEmetteur, $userRecepteur, $transaction, $typeTransaction);

            $message = match($typeTransaction) {
                'depot' => 'Dépôt effectué avec succès',
                'transfert' => 'Transfert effectué avec succès',
                'paiement' => 'Paiement effectué avec succès',
                default => 'Transaction effectuée avec succès'
            };

            return $this->respondCreated($transaction, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
