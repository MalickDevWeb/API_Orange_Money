<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\User;
use App\Interfaces\Services\CompteServiceInterface;
use App\Http\Requests\StoreCompteRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\PaginatedSortedTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="uuid-compte"),
 *     @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
 *     @OA\Property(property="titulaire", type="string", example="John Doe"),
 *     @OA\Property(property="code_marchand", type="string", nullable=true, example="MRC001"),
 *     @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"}, example="actif"),
 *     @OA\Property(property="utilisateur_id", type="string", format="uuid", example="uuid-user"),
 *     @OA\Property(property="solde", type="number", format="float", example=15000.50),
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
 * @property-read \App\Models\User $user
 * @method \Illuminate\Database\Eloquent\Relations\HasMany comptes()
 */
class CompteController extends Controller
{
    use ApiResponseTrait, PaginatedSortedTrait;

    protected CompteServiceInterface $compteService;

    public function __construct(CompteServiceInterface $compteService)
    {
        $this->compteService = $compteService;
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
     * @OA\Get(
     *     path="/comptes/me",
     *     tags={"Comptes"},
     *     summary="Afficher les comptes de l'utilisateur connecté",
     *     description="Affiche tous les comptes de l'utilisateur connecté avec le compte actif en premier par défaut.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Comptes récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Vos comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                 @OA\Property(property="titulaire", type="string", example="John Doe"),
     *                 @OA\Property(property="solde", type="number", format="float", example=15000.50),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="code_marchand", type="string", nullable=true, example="MRC001"),
     *                 @OA\Property(property="qr_code", type="string", nullable=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function me(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Check if supplier is approved
            if ($user->type === 'commercant' && $user->statut !== 'actif') {
                return $this->errorResponse('Votre compte fournisseur est en attente d\'approbation par l\'administrateur', 403);
            }

            $query = $user->comptes();

            // Pour les utilisateurs non-admin, trier pour que le compte actif soit en premier
            if ($user->type !== 'admin') {
                $comptes = $query->get()->sort(function ($a, $b) {
                    // Le compte actif doit être en premier
                    if ($a->statut === 'actif' && $b->statut !== 'actif') {
                        return -1;
                    }
                    if ($b->statut === 'actif' && $a->statut !== 'actif') {
                        return 1;
                    }
                    // Pour les comptes du même statut, trier par nom_compte
                    return strcmp($a->nom_compte, $b->nom_compte);
                })->values();
            } else {
                $comptes = $query->get();
            }

            $comptes = $comptes->map(function ($compte) {
                return [
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'solde' => $compte->solde,
                    'statut' => $compte->statut,
                    'code_marchand' => $compte->code_marchand,
                    'qr_code' => $compte->qr_code,
                ];
            });

            return $this->successResponse($comptes, 'Vos comptes récupérés avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/balance",
     *     tags={"Comptes"},
     *     summary="Consulter le solde du compte actif",
     *     description="Endpoint commun pour tous les utilisateurs authentifiés permettant de consulter le solde de leur compte actif (compte principal).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=15000.50),
     *                 @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *                 @OA\Property(property="nom_compte", type="string", example="compte principal")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Aucun compte actif trouvé")
     * )
     */
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
                'solde' => $compte->solde,
                'numero_compte' => $compte->numero_compte,
                'nom_compte' => $compte->nom_compte,
            ], 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function activate(string $nom_compte)
    {
        try {
            $user = auth()->user();

            // Trouver le compte par nom et utilisateur
            $compte = Compte::where('nom_compte', $nom_compte)
                          ->where('utilisateur_id', $user->id)
                          ->first();

            if (!$compte) {
                return $this->errorResponse('Compte non trouvé ou accès non autorisé', 404);
            }

            // Vérifier que le compte n'est pas déjà actif
            if ($compte->statut === 'actif') {
                return $this->errorResponse('Ce compte est déjà actif', 400);
            }

            // Gérer la désignation "compte principal"
            $userId = $compte->utilisateur_id;

            // Trouver le compte actuellement principal (actif)
            $currentPrincipalAccount = Compte::where('utilisateur_id', $userId)
                                           ->where('statut', 'actif')
                                           ->first();

            // Si le compte actuellement principal avait le nom "compte principal",
            // on lui donne un nom générique basé sur son numéro
            if ($currentPrincipalAccount && $currentPrincipalAccount->nom_compte === 'compte principal') {
                $currentPrincipalAccount->update([
                    'nom_compte' => 'compte secondaire ' . $currentPrincipalAccount->numero_compte,
                    'statut' => 'inactif'
                ]);
            } else {
                // Désactiver tous les autres comptes
                Compte::where('utilisateur_id', $userId)
                      ->where('nom_compte', '!=', $nom_compte)
                      ->update(['statut' => 'inactif']);
            }

            // Activer le compte demandé et le désigner comme "compte principal"
            $this->compteService->update($compte->id, [
                'statut' => 'actif',
                'nom_compte' => 'compte principal'
            ]);

            return $this->successResponse(null, 'Compte activé avec succès. Il est maintenant défini comme "compte principal".');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }


    /**
     * @OA\Get(
     *     path="/comptes",
     *     tags={"Comptes"},
     *     summary="Lister les comptes de l'utilisateur connecté",
     *     description="Affiche tous les comptes de l'utilisateur connecté avec le compte actif en premier par défaut. Possibilité de filtrer par statut, solde, numéro de compte, etc.",
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
     *                         @OA\Property(property="qr_code", type="string", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
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
                    'solde' => $compte->solde,
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
     * @OA\Post(
     *     path="/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un compte secondaire",
     *     description="Création de comptes secondaires pour utilisateurs existants. Le premier compte 'compte principal' est créé automatiquement lors de l'inscription.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Seul le nom du compte est requis. Tous les autres champs (numéro, solde, devise, etc.) sont auto-générés.",
     *         @OA\JsonContent(
     *             required={"nom_compte"},
     *             @OA\Property(property="nom_compte", type="string", example="compteprive", description="Nom unique du compte secondaire (différent de 'compte principal')")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte secondaire créé avec succès (statut inactif par défaut)",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=400, description="Nom de compte déjà utilisé ou réservé"),
     *     @OA\Response(response=403, description="Administrateur ne peut avoir qu'un compte")
     * )
     */
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

            // Vérifier que le nom du compte est unique pour cet utilisateur
            $existingAccountWithName = Compte::where('utilisateur_id', $user->id)
                                            ->where('nom_compte', $data['nom_compte'])
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

    /**
     * @OA\Get(
     *     path="/comptes/{compte}",
     *     tags={"Comptes"},
     *     summary="Afficher un compte spécifique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID du compte"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/comptes/{compte}",
     *     tags={"Comptes"},
     *     summary="Mettre à jour un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID du compte"
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", example="Jane Doe"),
     *             @OA\Property(property="code_marchand", type="string", example="MRC002"),
     *             @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"}, example="actif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
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

    /**
      * @OA\Delete(
      *     path="/comptes/{compte}",
      *     tags={"Comptes"},
      *     summary="Demander la suppression d'un compte (génère un code de confirmation)",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="compte",
      *         in="path",
      *         required=true,
      *         @OA\Schema(type="string"),
      *         description="ID du compte"
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Code de confirmation généré",
      *         @OA\JsonContent(
      *             @OA\Property(property="message", type="string", example="Code de confirmation envoyé. Utilisez ce code pour confirmer la suppression."),
      *             @OA\Property(property="code_sent", type="boolean", example=true)
      *         )
      *     ),
      *     @OA\Response(response=400, description="Solde positif ou compte déjà en cours de suppression"),
      *     @OA\Response(response=404, description="Compte non trouvé")
      * )
      */
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

    /**
     * @OA\Post(
     *     path="/comptes/confirm-delete",
     *     tags={"Comptes"},
     *     summary="Confirmer la suppression d'un compte avec le code de confirmation",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"confirmation_code","compte_id"},
     *             @OA\Property(property="confirmation_code", type="string", example="123456", description="Code de confirmation à 6 chiffres"),
     *             @OA\Property(property="compte_id", type="string", example="uuid-compte", description="ID du compte à supprimer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Code invalide, expiré ou ne correspond pas au compte"),
     *     @OA\Response(response=404, description="Code ou compte non trouvé")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/comptes/{compte}/restore",
     *     tags={"Comptes"},
     *     summary="Restaurer un compte supprimé",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID du compte"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte restauré",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/comptes/{compte}/force-delete",
     *     tags={"Comptes"},
     *     summary="Supprimer définitivement un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID du compte"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé définitivement",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compte supprimé définitivement")
     *         )
     *     )
     * )
     */
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
