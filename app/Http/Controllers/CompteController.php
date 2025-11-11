<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Interfaces\Services\CompteServiceInterface;
use App\Http\Requests\StoreCompteRequest;
use App\Traits\ApiResponseTrait;
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
 *     path="/api/comptes"
 * )
 * @OA\PathItem(
 *     path="/api/comptes/{compte}"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    protected CompteServiceInterface $compteService;

    public function __construct(CompteServiceInterface $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * @OA\Get(
     *     path="/api/comptes",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function index()
    {
        try {
            $comptes = $this->compteService->getAll();
            return $this->successResponse($comptes, 'Comptes récupérés avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero_compte","titulaire","statut"},
     *             @OA\Property(property="numero_compte", type="string", example="CMPT-001"),
     *             @OA\Property(property="titulaire", type="string", example="John Doe"),
     *             @OA\Property(property="code_marchand", type="string", example="MRC001"),
     *             @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"}, example="actif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function store(StoreCompteRequest $request)
    {
        try {
            // Ajouter l'utilisateur connecté aux données
            $data = array_merge($request->validated(), [
                'utilisateur_id' => auth('api')->id()
            ]);

            $compte = $this->compteService->create($data);
            return $this->respondCreated($compte, 'Compte créé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/comptes/{compte}",
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
            if ($compte->utilisateur_id !== auth('api')->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            return $this->successResponse($compte, 'Compte récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/comptes/{compte}",
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
            if ($compte->utilisateur_id !== auth('api')->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            $compte = $this->compteService->update($compte->id, $request->all());
            if (!$compte) {
                return $this->errorResponse('Erreur lors de la mise à jour du compte');
            }

            return $this->successResponse($compte, 'Compte mis à jour avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/comptes/{compte}",
     *     tags={"Comptes"},
     *     summary="Supprimer un compte (soft delete)",
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
     *         description="Compte supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function destroy(Compte $compte)
    {
        try {
            // Vérifier que l'utilisateur possède ce compte
            if ($compte->utilisateur_id !== auth('api')->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            $result = $this->compteService->delete($compte->id);
            if (!$result) {
                return $this->errorResponse('Erreur lors de la suppression du compte');
            }

            return $this->successResponse(null, 'Compte supprimé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/comptes/{compte}/restore",
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
            // Vérifier que l'utilisateur possède ce compte
            if ($compte->utilisateur_id !== auth('api')->id()) {
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
     *     path="/api/comptes/{compte}/force-delete",
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
            // Vérifier que l'utilisateur possède ce compte
            if ($compte->utilisateur_id !== auth('api')->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
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
