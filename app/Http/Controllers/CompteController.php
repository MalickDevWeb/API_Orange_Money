<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\User;
use App\Interfaces\Services\CompteServiceInterface;
use App\Interfaces\Services\EmailNotificationServiceInterface;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\NouveauCompteRequest;
use App\Http\Requests\ModifierCompteRequest;
use App\Http\Requests\ConfirmDeleteCompteRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\PaginatedSortedTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion complète des comptes bancaires avec règles métier strictes :
 *     - 1 compte principal créé automatiquement lors de l'inscription (actif)
 *     - Maximum 4 comptes par utilisateur (1 principal + 3 secondaires)
 *     - 1 seul compte actif à la fois (les autres deviennent inactifs automatiquement)
 *     - QR code généré automatiquement pour chaque compte
 *     - Notifications email automatiques lors de la création
 *     - Gestion complète du cycle de vie (création, modification, suppression avec OTP)"
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
 *     @OA\Property(property="titulaire", type="string", example="John Doe"),
 *     @OA\Property(property="nom_compte", type="string", example="compte principal"),
 *     @OA\Property(property="code_marchand", type="string", nullable=true, example="MRC001"),
 *     @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"}, example="actif"),
 *     @OA\Property(property="solde", type="number", format="float", example=0),
 *     @OA\Property(property="qr_code", type="string", format="base64", nullable=true, description="Code QR en base64 généré automatiquement"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\PathItem(
 *     path="/comptes"
 * )
 * @OA\PathItem(
 *     path="/comptes/{compte}"
 * )
 * @OA\PathItem(
 *     path="/balance"
 * )
 * @OA\PathItem(
 *     path="/comptes/activate/{nom_compte}"
 * )
 * @OA\PathItem(
 *     path="/comptes/confirm-delete"
 * )
 *
 * @mixin \App\Models\User
 */
class CompteController extends Controller
{
    use ApiResponseTrait, PaginatedSortedTrait;

    protected CompteServiceInterface $compteService;
    protected EmailNotificationServiceInterface $emailService;

    public function __construct(
        CompteServiceInterface $compteService,
        EmailNotificationServiceInterface $emailService
    ) {
        $this->compteService = $compteService;
        $this->emailService = $emailService;
    }

    protected function getAllowedSortFields()
    {
        return ['created_at', 'updated_at', 'numero_compte', 'statut'];
    }

    /**
     * Applique les filtres avancés à la requête des comptes
     */
    protected function applyFilters($query, Request $request)
    {
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
    }

    /**
     * @OA\Post(
     *     path="/compte/nouveaucompte",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte secondaire",
     *     description="Création d'un compte secondaire pour l'utilisateur connecté. Le compte est créé avec le statut 'inactif' par défaut. Limite : 4 comptes maximum par utilisateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_compte"},
     *             @OA\Property(property="nom_compte", type="string", example="compte epargne", description="Nom unique du compte secondaire")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Vous avez créé un nouveau compte du nom de compte epargne avec succès. Statut : inactif."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Nom de compte déjà utilisé, réservé ou limite de 4 comptes atteinte"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Administrateur ne peut avoir qu'un compte")
     * )
     */
    public function nouveaucompte(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Validation
            $request->validate([
                'nom_compte' => 'required|string|max:255',
            ]);

            // Vérifier que les admins n'ont qu'un seul compte
            if ($user->type === 'admin') {
                $existingAdminAccount = Compte::where('utilisateur_id', $user->id)->first();
                if ($existingAdminAccount) {
                    return $this->errorResponse('Les administrateurs ne peuvent avoir qu\'un seul compte.', 400);
                }
            }

            $nomCompte = $request->nom_compte;

            if ($nomCompte === 'compte principal') {
                return $this->errorResponse('Le nom "compte principal" est réservé.', 400);
            }

            // Vérifier la limite de 4 comptes par utilisateur
            $userComptesCount = $user->comptes()->count();
            if ($userComptesCount >= 4) {
                return $this->errorResponse('Vous ne pouvez pas avoir plus de 4 comptes.', 400);
            }

            // Vérifier que le nom du compte est unique pour cet utilisateur (insensible à la casse)
            $existingAccountWithName = Compte::where('utilisateur_id', $user->id)
                                            ->whereRaw('LOWER(nom_compte) = LOWER(?)', [$nomCompte])
                                            ->first();
            if ($existingAccountWithName) {
                return $this->errorResponse('Un compte avec ce nom existe déjà.', 400);
            }

            // Auto-générer les autres champs
            $data = [
                'numero_compte' => 'CMPT-' . strtoupper(uniqid()),
                'statut' => 'inactif', // Statut par défaut
                'titulaire' => $user->nom . ' ' . $user->prenom,
                'utilisateur_id' => $user->id,
                'client_id' => $user->id, // Même valeur que utilisateur_id
                'type_compte' => 'courant', // Type de compte par défaut
                'devise' => 'XOF', // Devise par défaut
                'nom_compte' => $nomCompte,
            ];

            $compte = $this->compteService->create($data);

            // Envoyer notification email avec le QR code
            try {
                $userData = [
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                ];

                $accountData = [
                    'nom_compte' => $compte->nom_compte,
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'type_compte' => $compte->type_compte ?? 'courant',
                    'devise' => $compte->devise ?? 'XOF',
                    'statut' => $compte->statut,
                    'solde' => $compte->solde,
                    'qr_code' => $compte->qr_code, // Inclure le QR code généré automatiquement
                ];

                $this->emailService->sendNewAccountNotification($userData, $accountData);
            } catch (\Exception $emailException) {
                // Log l'erreur mais ne pas échouer la création du compte
                \Illuminate\Support\Facades\Log::error('Erreur envoi email nouveau compte: ' . $emailException->getMessage(), [
                    'user_id' => $user->id,
                    'account_name' => $nomCompte
                ]);
            }

            return $this->successResponse(null, "Vous avez créé un nouveau compte du nom de {$nomCompte} avec succès. Statut : inactif.");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/mesComptes",
     *     tags={"Comptes"},
     *     summary="Récupérer tous les comptes de l'utilisateur",
     *     description="Liste tous les comptes de l'utilisateur connecté avec le compte actif en premier. Supporte la recherche, les filtres et la pagination.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif","inactif","suspendu"})
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
     *         @OA\Schema(type="string", enum={"created_at","updated_at","numero_compte","statut"})
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
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Vos comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                         @OA\Property(property="titulaire", type="string", example="John Doe"),
     *                         @OA\Property(property="solde", type="number", format="float", example=15000.5),
     *                         @OA\Property(property="statut", type="string", example="actif"),
     *                         @OA\Property(property="code_marchand", type="string", nullable=true, example="MRC001"),
     *                         @OA\Property(property="nom_compte", type="string", example="compte principal"),
     *                         @OA\Property(property="qr_code", type="string", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé pour les fournisseurs en attente")
     * )
     */
    public function mesComptes(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Check if supplier is approved
            if ($user->type === 'commercant' && $user->statut !== 'actif') {
                return $this->errorResponse('Votre compte fournisseur est en attente d\'approbation par l\'administrateur', 403);
            }

            $query = $user->comptes();

            // Appliquer les filtres
            $this->applyFilters($query, $request);

            // Pagination
            $comptes = $this->getPaginatedSorted($query, $request);

            // Trier pour que le compte actif soit en premier (si pas de tri personnalisé)
            if (!$request->has('sort_by')) {
                $collection = $comptes->getCollection();
                $sortedCollection = $collection->sort(function ($a, $b) {
                    // Le compte actif doit être en premier
                    if ($a->statut === 'actif' && $b->statut !== 'actif') {
                        return -1;
                    }
                    if ($b->statut === 'actif' && $a->statut !== 'actif') {
                        return 1;
                    }
                    // Pour les comptes du même statut, trier par nom_compte
                    return strcmp($a->nom_compte, $b->nom_compte);
                });
                $comptes->setCollection($sortedCollection->values());
            }

            // Formater les données de sortie
            $formattedData = $comptes->getCollection()->map(function ($compte) {
                return [
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'solde' => abs($compte->solde), // Toujours afficher le solde en valeur absolue (positive)
                    'statut' => $compte->statut,
                    'code_marchand' => $compte->code_marchand,
                    'nom_compte' => $compte->nom_compte,
                    'qr_code' => $compte->qr_code,
                ];
            });

            // Remplacer la collection dans l'objet paginé
            $comptes->setCollection($formattedData);

            return $this->successResponse($comptes, 'Vos comptes récupérés avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }


    public function solde()
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $compte = $user->comptes()->where('statut', 'actif')->first();
            if (!$compte) {
                return $this->errorResponse('Aucun compte actif trouvé', 404);
            }

            return $this->successResponse([
                'solde' => abs($compte->solde), // Toujours afficher le solde en valeur absolue
                'numero_compte' => $compte->numero_compte,
                'nom_compte' => $compte->nom_compte,
            ], 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }



    public function index(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Check if supplier is approved
            if ($user->type === 'commercant' && $user->statut !== 'actif') {
                return $this->errorResponse('Votre compte fournisseur est en attente d\'approbation par l\'administrateur', 403);
            }

            $query = $user->comptes();

            // Appliquer les filtres
            $this->applyFilters($query, $request);

            $comptes = $this->getPaginatedSorted($query, $request);

            // Trier pour que le compte actif soit en premier (si pas de tri personnalisé)
            if (!$request->has('sort_by')) {
                $collection = $comptes->getCollection();
                $sortedCollection = $collection->sort(function ($a, $b) {
                    // Le compte actif doit être en premier
                    if ($a->statut === 'actif' && $b->statut !== 'actif') {
                        return -1;
                    }
                    if ($b->statut === 'actif' && $a->statut !== 'actif') {
                        return 1;
                    }
                    // Pour les comptes du même statut, trier par nom_compte
                    return strcmp($a->nom_compte, $b->nom_compte);
                });
                $comptes->setCollection($sortedCollection->values());
            }

            // Formater les données de sortie
            $formattedData = $comptes->getCollection()->map(function ($compte) {
                return [
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'solde' => abs($compte->solde), // Toujours afficher le solde en valeur absolue
                    'statut' => $compte->statut,
                    'code_marchand' => $compte->code_marchand,
                    'qr_code' => $compte->qr_code,
                ];
            });

            // Remplacer la collection dans l'objet paginé
            $comptes->setCollection($formattedData);

            return $this->successResponse($comptes, 'Vos comptes récupérés avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numeroCompte}",
     *     tags={"Comptes"},
     *     summary="Récupérer un compte spécifique par numéro",
     *     description="Récupère les détails d'un compte spécifique en utilisant son numéro de compte.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Numéro du compte à récupérer",
     *         example="CMPT-001"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                 @OA\Property(property="titulaire", type="string", example="John Doe"),
     *                 @OA\Property(property="solde", type="number", format="float", example=15000.5),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="code_marchand", type="string", nullable=true, example="MRC001"),
     *                 @OA\Property(property="nom_compte", type="string", example="compte principal"),
     *                 @OA\Property(property="qr_code", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function showByNumero(string $numeroCompte)
    {
        try {
            $user = auth()->user();

            $compte = Compte::where('numero_compte', $numeroCompte)
                           ->where('utilisateur_id', $user->id)
                           ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            return $this->successResponse([
                'numero_compte' => $compte->numero_compte,
                'titulaire' => $compte->titulaire,
                'solde' => abs($compte->solde), // Toujours afficher le solde en valeur absolue
                'statut' => $compte->statut,
                'code_marchand' => $compte->code_marchand,
                'nom_compte' => $compte->nom_compte,
                'qr_code' => $compte->qr_code,
                'created_at' => $compte->created_at,
                'updated_at' => $compte->updated_at,
            ], 'Compte récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/compte/{numeroCompte}/solde",
     *     tags={"Comptes"},
     *     summary="Consulter le solde d'un compte spécifique",
     *     description="Récupère le solde d'un compte spécifique en utilisant son numéro de compte.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Numéro du compte",
     *         example="CMPT-001"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=15000.5),
     *                 @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                 @OA\Property(property="nom_compte", type="string", example="compte principal")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function soldeByNumero(string $numeroCompte)
    {
        try {
            $user = auth()->user();

            $compte = Compte::where('numero_compte', $numeroCompte)
                           ->where('utilisateur_id', $user->id)
                           ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            return $this->successResponse([
                'solde' => abs($compte->solde), // Toujours afficher le solde en valeur absolue
                'numero_compte' => $compte->numero_compte,
                'nom_compte' => $compte->nom_compte,
            ], 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/compte/{numeroCompte}/modifier",
     *     tags={"Comptes"},
     *     summary="Modifier un compte existant",
     *     description="Modifie les informations d'un compte existant (titulaire, code marchand, statut, nom du compte).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Numéro du compte à modifier",
     *         example="CMPT-001"
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", example="Jane Doe"),
     *             @OA\Property(property="code_marchand", type="string", example="MRC002"),
     *             @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"}, example="actif"),
     *             @OA\Property(property="nom_compte", type="string", example="compte epargne")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides ou nom de compte déjà utilisé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function modifier(ModifierCompteRequest $request, string $numeroCompte)
    {
        try {
            $user = auth()->user();

            $compte = Compte::where('numero_compte', $numeroCompte)
                           ->where('utilisateur_id', $user->id)
                           ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            $updateData = $request->validated();

            // Vérifier l'unicité du nom_compte si modifié
            if (isset($updateData['nom_compte']) && $updateData['nom_compte'] !== $compte->nom_compte) {
                if ($updateData['nom_compte'] === 'compte principal') {
                    return $this->errorResponse('Le nom "compte principal" est réservé.', 400);
                }

                $existingAccountWithName = Compte::where('utilisateur_id', $user->id)
                                                ->whereRaw('LOWER(nom_compte) = LOWER(?)', [$updateData['nom_compte']])
                                                ->where('id', '!=', $compte->id)
                                                ->first();
                if ($existingAccountWithName) {
                    return $this->errorResponse('Un compte avec ce nom existe déjà.', 400);
                }
            }

            // Si le statut est changé à 'actif', désactiver tous les autres comptes
            if (isset($updateData['statut']) && $updateData['statut'] === 'actif' && $compte->statut !== 'actif') {
                // Désactiver tous les autres comptes
                Compte::where('utilisateur_id', $user->id)
                      ->where('id', '!=', $compte->id)
                      ->update(['statut' => 'inactif']);

                // Le compte qui devient actif prend le nom "compte principal"
                $updateData['nom_compte'] = 'compte principal';
            }

            $compte = $this->compteService->update($compte->id, $updateData);

            if (!$compte) {
                return $this->errorResponse('Erreur lors de la mise à jour du compte');
            }

            // Envoyer notification email pour la modification
            try {
                $userData = [
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                ];

                $accountData = [
                    'nom_compte' => $compte->nom_compte,
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'type_compte' => $compte->type_compte ?? 'courant',
                    'devise' => $compte->devise ?? 'XOF',
                    'statut' => $compte->statut,
                    'solde' => $compte->solde,
                ];

                // Calculer les changements pour l'email
                $changes = [];
                foreach ($updateData as $field => $newValue) {
                    $oldValue = $compte->getOriginal($field);
                    if ($oldValue != $newValue) {
                        $changes[$field] = [
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                    }
                }

                $this->emailService->sendAccountModificationNotification($userData, $accountData, $changes);
            } catch (\Exception $emailException) {
                // Log l'erreur mais ne pas échouer la modification
                \Illuminate\Support\Facades\Log::error('Erreur envoi email modification compte: ' . $emailException->getMessage(), [
                    'user_id' => $user->id,
                    'account_name' => $compte->nom_compte
                ]);
            }

            $message = isset($updateData['statut']) && $updateData['statut'] === 'actif'
                ? 'Compte mis à jour avec succès. Tous les autres comptes ont été désactivés.'
                : 'Compte mis à jour avec succès';

            return $this->successResponse($compte, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/compte/{numeroCompte}/switch",
     *     tags={"Comptes"},
     *     summary="Changer le compte actif",
     *     description="Change le compte actif de l'utilisateur. Désactive automatiquement tous les autres comptes et définit le compte sélectionné comme 'compte principal'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Numéro du compte à activer",
     *         example="CMPT-002"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte activé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Compte activé avec succès. Il est maintenant défini comme 'compte principal'."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="numero_compte", type="string", example="CMPT-002"),
     *                 @OA\Property(property="nom_compte", type="string", example="compte principal"),
     *                 @OA\Property(property="statut", type="string", example="actif")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Compte déjà actif ou suspendu"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function switch(string $numeroCompte)
    {
        try {
            $user = auth()->user();

            $compte = Compte::where('numero_compte', $numeroCompte)
                           ->where('utilisateur_id', $user->id)
                           ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            // Vérifier que le compte n'est pas déjà actif
            if ($compte->statut === 'actif') {
                return $this->errorResponse('Ce compte est déjà actif', 400);
            }

            // Vérifier que le compte n'est pas suspendu
            if ($compte->statut === 'suspendu') {
                return $this->errorResponse('Impossible d\'activer un compte suspendu', 400);
            }

            // Désactiver tous les comptes actifs
            Compte::where('utilisateur_id', $user->id)
                  ->where('statut', 'actif')
                  ->update(['statut' => 'inactif']);

            // Gérer le nom "compte principal" pour l'ancien compte actif
            $oldActiveAccount = Compte::where('utilisateur_id', $user->id)
                                     ->where('nom_compte', 'compte principal')
                                     ->first();
            if ($oldActiveAccount) {
                $oldActiveAccount->update([
                    'nom_compte' => 'compte secondaire ' . $oldActiveAccount->numero_compte
                ]);
            }

            // Activer le nouveau compte et le nommer "compte principal"
            $compte->update([
                'statut' => 'actif',
                'nom_compte' => 'compte principal'
            ]);

            // Envoyer notification email pour le changement de compte actif
            try {
                $userData = [
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                ];

                $oldAccountData = $oldActiveAccount ? [
                    'nom_compte' => $oldActiveAccount->nom_compte,
                    'numero_compte' => $oldActiveAccount->numero_compte,
                    'statut' => 'inactif',
                ] : null;

                $newAccountData = [
                    'nom_compte' => $compte->nom_compte,
                    'numero_compte' => $compte->numero_compte,
                    'statut' => $compte->statut,
                ];

                $this->emailService->sendAccountSwitchNotification($userData, $oldAccountData, $newAccountData);
            } catch (\Exception $emailException) {
                // Log l'erreur mais ne pas échouer le switch
                \Illuminate\Support\Facades\Log::error('Erreur envoi email changement compte actif: ' . $emailException->getMessage(), [
                    'user_id' => $user->id,
                    'new_account' => $compte->nom_compte
                ]);
            }

            return $this->successResponse([
                'numero_compte' => $compte->numero_compte,
                'nom_compte' => $compte->nom_compte,
                'statut' => $compte->statut,
            ], 'Compte activé avec succès. Il est maintenant défini comme "compte principal".');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/compte/{numeroCompte}/supprimer",
     *     tags={"Comptes"},
     *     summary="Demander la suppression d'un compte",
     *     description="Initie le processus de suppression d'un compte selon les règles métier. Peut nécessiter une confirmation OTP.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Numéro du compte à supprimer",
     *         example="CMPT-001"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande de suppression initiée",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="success"),
     *                     @OA\Property(property="message", type="string", example="Votre compte compte epargne est supprimé avec succès."),
     *                     @OA\Property(property="data", type="null")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="success"),
     *                     @OA\Property(property="message", type="string", example="Confirmation OTP requise pour la suppression du compte."),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="requires_otp", type="boolean", example=true),
     *                         @OA\Property(property="compte_id", type="string", example="uuid-compte"),
     *                         @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                         @OA\Property(property="nom_compte", type="string", example="compte principal"),
     *                         @OA\Property(property="solde", type="number", format="float", example=0),
     *                         @OA\Property(property="otp_sent", type="boolean", example=true),
     *                         @OA\Property(property="expires_in", type="string", example="10 minutes")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=400, description="Solde positif ou règles métier non respectées"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function supprimer(string $numeroCompte)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            $compte = Compte::where('numero_compte', $numeroCompte)
                           ->where('utilisateur_id', $user->id)
                           ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            /*
             * SCÉNARIO DE SUPPRESSION DE COMPTE AVEC GESTION OTP
             *
             * Règles métier pour la suppression de comptes :
             *
             * 1. COMPTE ACTIF :
             *    - Si utilisateur a ≥ 2 comptes :
             *      → Interdit : Doit d'abord switcher vers un autre compte actif
             *      → Message : "Vous devez d'abord activer un autre compte avant de supprimer celui-ci."
             *
             *    - Si utilisateur a 1 seul compte (compte unique actif) :
             *      → Toujours nécessite OTP (quel que soit le solde)
             *      → Type OTP : 'delete_unique_account' ou 'delete_unique_account_zero_balance'
             *
             * 2. COMPTE INACTIF :
             *    - Si solde > 0 :
             *      → Interdit : Impossible de supprimer un compte avec solde positif
             *      → Message : "Impossible de supprimer un compte avec un solde positif."
             *
             *    - Si solde ≤ 0 :
             *      → Suppression directe sans OTP
             *      → Message : "Votre compte [nom] est supprimé avec succès."
             *
             * 3. GESTION OTP :
             *    - Code généré : 6 chiffres aléatoires
             *    - Validité : 10 minutes
             *    - Stockage : Table otp_codes avec métadonnées du compte
             *    - Confirmation : Via endpoint POST /otp/confirmation
             */

            // Règles métier pour la suppression
            $userComptesCount = $user->comptes()->count();

            if ($compte->statut === 'actif') {
                // SCÉNARIO 1A : Compte actif avec ≥ 2 comptes utilisateur
                if ($userComptesCount >= 2) {
                    // Switch obligatoire avant suppression
                    return $this->errorResponse('Vous devez d\'abord activer un autre compte avant de supprimer celui-ci.', 400);
                } else {
                    // SCÉNARIO 1B : Compte unique actif - OTP obligatoire
                    if ($compte->solde > 0) {
                        // Générer OTP pour compte unique avec solde positif
                        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                        \App\Models\OtpCode::create([
                            'telephone' => $user->telephone,
                            'code' => $otpCode,
                            'type' => 'delete_unique_account', // Type spécifique pour compte unique
                            'data' => [
                                'compte_id' => $compte->id,
                                'numero_compte' => $compte->numero_compte,
                                'nom_compte' => $compte->nom_compte,
                                'solde' => $compte->solde,
                            ],
                            'expires_at' => now()->addMinutes(10),
                        ]);

                        return $this->successResponse([
                            'requires_otp' => true,
                            'compte_id' => $compte->id,
                            'numero_compte' => $compte->numero_compte,
                            'nom_compte' => $compte->nom_compte,
                            'solde' => $compte->solde,
                            'otp_sent' => true,
                            'expires_in' => '10 minutes'
                        ], 'Suppression du compte unique nécessite une confirmation OTP.');
                    } else {
                        // Générer OTP pour compte unique avec solde ≤ 0
                        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                        \App\Models\OtpCode::create([
                            'telephone' => $user->telephone,
                            'code' => $otpCode,
                            'type' => 'delete_unique_account_zero_balance', // Type spécifique
                            'data' => [
                                'compte_id' => $compte->id,
                                'numero_compte' => $compte->numero_compte,
                                'nom_compte' => $compte->nom_compte,
                                'solde' => $compte->solde,
                            ],
                            'expires_at' => now()->addMinutes(10),
                        ]);

                        return $this->successResponse([
                            'requires_otp' => true,
                            'compte_id' => $compte->id,
                            'numero_compte' => $compte->numero_compte,
                            'nom_compte' => $compte->nom_compte,
                            'solde' => $compte->solde,
                            'otp_sent' => true,
                            'expires_in' => '10 minutes'
                        ], 'Confirmation OTP requise pour la suppression du compte.');
                    }
                }
            } else {
                // SCÉNARIO 2 : Compte inactif
                if ($compte->solde > 0) {
                    // Interdit : Solde positif
                    return $this->errorResponse('Impossible de supprimer un compte avec un solde positif.', 400);
                } else {
                    // SCÉNARIO 2B : Compte inactif avec solde ≤ 0 - Suppression directe
                    $result = $this->compteService->delete($compte->id);
                    if (!$result) {
                        return $this->errorResponse('Erreur lors de la suppression du compte');
                    }

                    return $this->successResponse(null, 'Votre compte ' . $compte->nom_compte . ' est supprimé avec succès.');
                }
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/otp/confirmation",
     *     tags={"Comptes"},
     *     summary="Confirmer la suppression d'un compte avec OTP",
     *     description="Valide le code OTP et procède à la suppression définitive du compte.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"otp_code","compte_id"},
     *             @OA\Property(property="otp_code", type="string", example="123456", description="Code OTP à 6 chiffres"),
     *             @OA\Property(property="compte_id", type="string", example="uuid-compte", description="ID du compte à supprimer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Votre compte compte epargne est supprimé avec succès après confirmation OTP."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Code OTP invalide ou expiré"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Code ou compte non trouvé")
     * )
     */
    public function confirmationOtp(ConfirmDeleteCompteRequest $request)
    {
        try {
            $user = auth()->user();

            $data = $request->validated();

            // Trouver le code OTP valide
            $otpCode = \App\Models\OtpCode::where('telephone', $user->telephone)
                ->where('code', $data['otp_code'])
                ->whereIn('type', ['delete_unique_account', 'delete_unique_account_zero_balance'])
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->first();

            if (!$otpCode || !isset($otpCode->data['compte_id'])) {
                return $this->errorResponse('Code OTP invalide ou expiré', 400);
            }

            // Vérifier que l'ID du compte correspond
            if ($otpCode->data['compte_id'] !== $data['compte_id']) {
                return $this->errorResponse('Code OTP ne correspond pas au compte spécifié', 400);
            }

            // Vérifier que le compte existe et appartient à l'utilisateur
            $compte = Compte::find($data['compte_id']);
            if (!$compte || $compte->utilisateur_id !== $user->id) {
                return $this->errorResponse('Compte non trouvé ou accès non autorisé', 404);
            }

            // Marquer le code comme utilisé
            $otpCode->markAsUsed();

            // Supprimer le compte
            $result = $this->compteService->delete($compte->id);
            if (!$result) {
                return $this->errorResponse('Erreur lors de la suppression du compte');
            }

            // Envoyer notification email pour la suppression
            try {
                $userData = [
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                ];

                $accountData = [
                    'nom_compte' => $compte->nom_compte,
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise ?? 'XOF',
                ];

                $reason = $otpCode->type === 'delete_unique_account'
                    ? 'Suppression du compte unique avec solde positif'
                    : 'Suppression du compte unique avec solde nul ou négatif';

                $this->emailService->sendAccountDeletionNotification($userData, $accountData, $reason);
            } catch (\Exception $emailException) {
                // Log l'erreur mais ne pas échouer la suppression
                \Illuminate\Support\Facades\Log::error('Erreur envoi email suppression compte: ' . $emailException->getMessage(), [
                    'user_id' => $user->id,
                    'account_name' => $compte->nom_compte
                ]);
            }

            return $this->successResponse(null, 'Votre compte ' . $compte->nom_compte . ' est supprimé avec succès après confirmation OTP.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/compte/{numeroCompte}/restaurer",
     *     tags={"Comptes"},
     *     summary="Restaurer un compte supprimé",
     *     description="Restaure un compte qui a été supprimé (soft delete).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Numéro du compte à restaurer",
     *         example="CMPT-001"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte restauré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Compte restauré avec succès."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                 @OA\Property(property="nom_compte", type="string", example="compte principal"),
     *                 @OA\Property(property="statut", type="string", example="inactif"),
     *                 @OA\Property(property="solde", type="number", format="float", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ce compte n'est pas supprimé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function restaurer(string $numeroCompte)
    {
        try {
            $user = auth()->user();

            // Trouver le compte supprimé (soft delete)
            $compte = Compte::withTrashed()
                           ->where('numero_compte', $numeroCompte)
                           ->where('utilisateur_id', $user->id)
                           ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            if (!$compte->trashed()) {
                return $this->errorResponse('Ce compte n\'est pas supprimé', 400);
            }

            // Restaurer le compte
            $compte = $this->compteService->restore($compte->id);

            if (!$compte) {
                return $this->errorResponse('Erreur lors de la restauration du compte');
            }

            // Envoyer notification email pour la restauration
            try {
                $userData = [
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                ];

                $accountData = [
                    'nom_compte' => $compte->nom_compte,
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'statut' => $compte->statut,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise ?? 'XOF',
                ];

                $this->emailService->sendAccountRestorationNotification($userData, $accountData);
            } catch (\Exception $emailException) {
                // Log l'erreur mais ne pas échouer la restauration
                \Illuminate\Support\Facades\Log::error('Erreur envoi email restauration compte: ' . $emailException->getMessage(), [
                    'user_id' => $user->id,
                    'account_name' => $compte->nom_compte
                ]);
            }

            return $this->successResponse([
                'numero_compte' => $compte->numero_compte,
                'nom_compte' => $compte->nom_compte,
                'statut' => $compte->statut,
                'solde' => abs($compte->solde), // Toujours afficher le solde en valeur absolue
            ], 'Votre compte ' . $compte->nom_compte . ' est restauré avec succès.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            // Vérifier que les admins n'ont qu'un seul compte
            if ($user->type === 'admin') {
                $existingAdminAccount = Compte::where('utilisateur_id', $user->id)->first();
                if ($existingAdminAccount) {
                    return $this->errorResponse('Les administrateurs ne peuvent avoir qu\'un seul compte.', 400);
                }
            }

            // Tous les comptes créés via l'API sont maintenant secondaires
            // Le premier compte est créé automatiquement lors de l'inscription

            // Validation pour comptes secondaires : seulement nom_compte requis
            $request->validate([
                'nom_compte' => 'required|string',
            ]);

            $data = $request->only(['nom_compte']);

            if ($data['nom_compte'] === 'compte principal') {
                return $this->errorResponse('Le nom "compte principal" est réservé au premier compte créé automatiquement lors de l\'inscription.', 400);
            }

            // Vérifier que le nom du compte est unique pour cet utilisateur (insensible à la casse)
            $existingAccountWithName = Compte::where('utilisateur_id', $user->id)
                                            ->whereRaw('LOWER(nom_compte) = LOWER(?)', [$data['nom_compte']])
                                            ->first();
            if ($existingAccountWithName) {
                return $this->errorResponse('Un compte avec ce nom existe déjà.', 400);
            }

            // Auto-générer tous les champs requis pour les comptes secondaires
            $data['numero_compte'] = 'CMPT-' . strtoupper(uniqid());
            $data['client_id'] = $user->id; // Utilise l'ID de l'utilisateur comme client_id
            $data['type_compte'] = 'courant'; // Type par défaut
            $data['devise'] = 'XOF'; // Devise par défaut
            $data['statut'] = 'inactif'; // Les comptes secondaires sont créés inactifs
            $data['titulaire'] = $user->nom . ' ' . $user->prenom; // Nom complet de l'utilisateur
            $data['utilisateur_id'] = auth()->id();

            $compte = $this->compteService->create($data);

            return $this->respondCreated($compte, 'Compte secondaire "' . $data['nom_compte'] . '" créé avec succès. Il est inactif par défaut.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(Compte $compte)
    {
        try {
            // Vérifier que l'utilisateur possède ce compte
            if ($compte->utilisateur_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            return $this->successResponse($compte, 'Compte récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, Compte $compte)
    {
        try {
            // Vérifier que l'utilisateur possède ce compte
            if ($compte->utilisateur_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            $updateData = $request->all();

            // Si le statut est changé à 'actif', désactiver tous les autres comptes et gérer "compte principal"
            if (isset($updateData['statut']) && $updateData['statut'] === 'actif' && $compte->statut !== 'actif') {
                $userId = $compte->utilisateur_id;

                // Trouver le compte actuellement principal (actif)
                $currentPrincipalAccount = Compte::where('utilisateur_id', $userId)
                                               ->where('statut', 'actif')
                                               ->first();

                // Si le compte actuellement principal avait le nom "compte principal",
                // on lui donne un nom générique
                if ($currentPrincipalAccount && $currentPrincipalAccount->nom_compte === 'compte principal') {
                    $currentPrincipalAccount->update([
                        'nom_compte' => 'compte secondaire ' . $currentPrincipalAccount->numero_compte,
                        'statut' => 'inactif'
                    ]);
                } else {
                    // Désactiver tous les autres comptes
                    Compte::where('utilisateur_id', $userId)
                          ->where('id', '!=', $compte->id)
                          ->update(['statut' => 'inactif']);
                }

                // Le compte qui devient actif prend le nom "compte principal"
                $updateData['nom_compte'] = 'compte principal';
            }

            $compte = $this->compteService->update($compte->id, $updateData);
            if (!$compte) {
                return $this->errorResponse('Erreur lors de la mise à jour du compte');
            }

            $message = isset($updateData['statut']) && $updateData['statut'] === 'actif'
                ? 'Compte mis à jour avec succès. Tous les autres comptes ont été désactivés.'
                : 'Compte mis à jour avec succès';

            return $this->successResponse($compte, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(Request $request, Compte $compte)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            // Vérifier les permissions
            if ($user->type !== 'admin' && $compte->utilisateur_id !== $user->id) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            // Vérifier que le solde est <= 0
            if ($compte->solde > 0) {
                return $this->errorResponse('Impossible de supprimer un compte avec un solde positif', 400);
            }

            // Générer un code de confirmation à 6 chiffres
            $confirmationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Stocker le code dans la base de données avec les données du compte
            $otpCode = \App\Models\OtpCode::create([
                'telephone' => $user->telephone,
                'code' => $confirmationCode,
                'type' => 'delete_compte',
                'data' => [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'nom_compte' => $compte->nom_compte
                ],
                'expires_at' => now()->addMinutes(10), // Code valide 10 minutes
            ]);

            // Ici, vous pourriez envoyer le code par SMS/email
            // Pour l'instant, on le retourne dans la réponse (à des fins de test)

            return $this->successResponse([
                'code_sent' => true,
                'confirmation_code' => $confirmationCode, // À retirer en production
                'compte_id' => $compte->id,
                'nom_compte' => $compte->nom_compte,
                'expires_in' => '10 minutes'
            ], 'Code de confirmation généré. Utilisez ce code pour confirmer la suppression.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function confirmDelete(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            $data = $request->validate([
                'confirmation_code' => 'required|string|size:6',
                'compte_id' => 'required|string|exists:comptes,id', // Ajouter validation du compte
            ]);

            // Trouver le code OTP valide pour la suppression de compte
            $otpCode = \App\Models\OtpCode::findValidCode(
                $data['confirmation_code'],
                $user->telephone,
                'delete_compte'
            );

            if (!$otpCode || !isset($otpCode->data['compte_id'])) {
                return $this->errorResponse('Code de confirmation invalide ou expiré', 400);
            }

            $compteId = $otpCode->data['compte_id'];

            // Vérifier que l'ID du compte dans le code correspond à celui fourni
            if ($compteId !== $data['compte_id']) {
                return $this->errorResponse('Code de confirmation ne correspond pas au compte spécifié', 400);
            }

            // Vérifier que le compte existe et appartient à l'utilisateur
            $compte = Compte::find($compteId);
            if (!$compte || $compte->utilisateur_id !== $user->id) {
                return $this->errorResponse('Compte non trouvé ou accès non autorisé', 404);
            }

            // Marquer le code comme utilisé
            $otpCode->markAsUsed();

            // Supprimer le compte (soft delete)
            $result = $this->compteService->delete($compte->id);
            if (!$result) {
                return $this->errorResponse('Erreur lors de la suppression du compte');
            }

            // Si le compte supprimé était actif, activer un autre compte
            if ($compte->statut === 'actif') {
                $otherCompte = $user->comptes()->where('statut', '!=', 'actif')->first();
                if ($otherCompte) {
                    $this->compteService->update($otherCompte->id, ['statut' => 'actif']);
                }
            }

            return $this->successResponse(null, 'Compte "' . $compte->nom_compte . '" supprimé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function restore(Compte $compte)
    {
        try {
            $user = auth()->user();
            // Vérifier les permissions
            if ($user->type !== 'admin' && $compte->utilisateur_id !== $user->id) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            $compte = $this->compteService->restore($compte->id);
            if (!$compte) {
                return $this->errorResponse('Erreur lors de la restauration du compte');
            }

            return $this->successResponse($compte, 'Compte restauré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function forceDelete(Compte $compte)
    {
        try {
            $user = auth()->user();
            // Vérifier les permissions
            if ($user->type !== 'admin' && $compte->utilisateur_id !== $user->id) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            // Vérifier que le solde est <= 0
            if ($compte->solde > 0) {
                return $this->errorResponse('Impossible de supprimer un compte avec un solde positif', 400);
            }

            $result = $this->compteService->forceDelete($compte->id);
            if (!$result) {
                return $this->errorResponse('Erreur lors de la suppression définitive du compte');
            }

            return $this->successResponse(null, 'Compte supprimé définitivement');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
