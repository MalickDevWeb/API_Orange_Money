<?php

namespace App\Http\Controllers;

use App\Models\Compte;
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
 *     path="/api/comptes"
 * )
 * @OA\PathItem(
 *     path="/api/comptes/{compte}"
 * )
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
        return ['created_at', 'updated_at', 'numero_compte', 'solde', 'statut'];
    }

    /**
     * @OA\Get(
     *     path="/api/comptes/me",
     *     tags={"Comptes"},
     *     summary="Afficher les comptes de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="all",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean"),
     *         description="Inclure tous les comptes (par défaut seulement actifs)"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comptes récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Votre compte actif récupéré avec succès"),
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
            $user = auth()->user();

            // Check if supplier is approved
            if ($user->type === 'commercant' && $user->statut !== 'actif') {
                return $this->errorResponse('Votre compte fournisseur est en attente d\'approbation par l\'administrateur', 403);
            }

            $query = $user->comptes();

            if (!$request->has('all')) {
                $query->where('statut', 'actif');
            }

            $comptes = $query->get()->map(function ($compte) {
                return [
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $compte->titulaire,
                    'solde' => $compte->solde,
                    'statut' => $compte->statut,
                    'code_marchand' => $compte->code_marchand,
                    'qr_code' => $compte->qr_code,
                ];
            });

            return $this->successResponse($comptes, 'Votre compte actif récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function solde()
    {
        try {
            $compte = auth()->user()->comptes()->where('statut', 'actif')->first();
            if (!$compte) {
                return $this->errorResponse('Aucun compte actif trouvé', 404);
            }

            return $this->successResponse([
                'solde' => $compte->solde,
                'numero_compte' => $compte->numero_compte,
            ], 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function creer(Request $request)
    {
        try {
            $nom_compte = $request->query('nom_compte', 'Mon Compte');

            // Générer numero_compte
            $numero_compte = 'CMPT-' . strtoupper(substr($nom_compte, 0, 10)) . '-' . rand(100, 999);

            // Vérifier unicité
            while (\App\Models\Compte::where('numero_compte', $numero_compte)->exists()) {
                $numero_compte = 'CMPT-' . strtoupper(substr($nom_compte, 0, 10)) . '-' . rand(100, 999);
            }

            $data = [
                'numero_compte' => $numero_compte,
                'titulaire' => auth()->user()->nom . ' ' . auth()->user()->prenom,
                'utilisateur_id' => auth()->id(),
                'statut' => 'inactif', // Par défaut inactif
                'solde' => 0,
            ];

            $compte = $this->compteService->create($data);

            return $this->successResponse($compte, 'Compte créé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function activate($numero_compte)
    {
        try {
            $compte = auth()->user()->comptes()->where('numero_compte', $numero_compte)->first();
            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            $this->compteService->update($compte->id, ['statut' => 'actif']);

            return $this->successResponse(null, 'Compte activé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function supprimerGet($numero_compte)
    {
        try {
            $compte = auth()->user()->comptes()->where('numero_compte', $numero_compte)->first();
            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            // Vérifier que le solde est <= 0
            if ($compte->solde > 0) {
                return $this->errorResponse('Impossible de supprimer un compte avec un solde positif', 400);
            }

            if ($compte->statut === 'actif') {
                // Générer OTP pour confirmation
                $otp = \App\Models\OtpCode::createForUser(
                    auth()->id(),
                    auth()->user()->telephone,
                    'delete_compte',
                    ['numero_compte' => $numero_compte]
                );

                // Envoyer OTP par SMS
                $twilioService = app(\App\Services\TwilioService::class);
                $smsSent = $twilioService->sendOtp('+221' . auth()->user()->telephone, $otp->code);

                if (!$smsSent) {
                    // Fallback email
                    $emailService = app(\App\Interfaces\Notifications\EmailServiceInterface::class);
                    $emailSent = $emailService->sendEmailOtp(auth()->user()->email, $otp->code, auth()->user()->nom . ' ' . auth()->user()->prenom);
                }

                $response = [
                    'requires_confirmation' => true,
                    'message' => 'Un code de confirmation a été envoyé pour supprimer le compte actif.'
                ];

                if (app()->environment('local')) {
                    $response['otp_code'] = $otp->code; // Pour test en développement
                }

                return $this->successResponse($response, 'Confirmation requise');
            } else {
                // Supprimer directement
                $result = $this->compteService->delete($compte->id);
                if (!$result) {
                    return $this->errorResponse('Erreur lors de la suppression du compte');
                }

                return $this->successResponse(null, 'Compte supprimé avec succès');
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function supprimer(Request $request)
    {
        try {
            $data = $request->validate([
                'numero_compte' => 'required|string',
                'otp_code' => 'required|string|size:6',
            ]);

            $compte = auth()->user()->comptes()->where('numero_compte', $data['numero_compte'])->first();
            if (!$compte) {
                return $this->errorResponse('Compte non trouvé', 404);
            }

            // Vérifier que le solde est <= 0
            if ($compte->solde > 0) {
                return $this->errorResponse('Impossible de supprimer un compte avec un solde positif', 400);
            }

            // Vérifier OTP
            $otpCode = \App\Models\OtpCode::findValidCode(
                $data['otp_code'],
                auth()->user()->telephone,
                'delete_compte'
            );

            if (!$otpCode || !isset($otpCode->data['numero_compte']) || $otpCode->data['numero_compte'] !== $data['numero_compte']) {
                return $this->errorResponse('Code OTP invalide', 400);
            }

            $otpCode->markAsUsed();

            $result = $this->compteService->delete($compte->id);
            if (!$result) {
                return $this->errorResponse('Erreur lors de la suppression du compte');
            }

            // Si le compte supprimé était actif, activer un autre compte
            if ($compte->statut === 'actif') {
                $otherCompte = auth()->user()->comptes()->where('statut', '!=', 'actif')->first();
                if ($otherCompte) {
                    $this->compteService->update($otherCompte->id, ['statut' => 'actif']);
                }
            }

            return $this->successResponse(null, 'Compte supprimé avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
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
    public function index(Request $request)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return $this->errorResponse('Accès non autorisé. Seuls les administrateurs peuvent lister tous les comptes.', 403);
            }

            $query = Compte::query();
            $comptes = $this->getPaginatedSorted($query, $request);
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
                'utilisateur_id' => auth()->id()
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
            if ($compte->utilisateur_id !== auth()->id()) {
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
            // Vérifier les permissions
            if (!auth()->user()->isAdmin() && $compte->utilisateur_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            // Vérifier que le solde est <= 0
            if ($compte->solde > 0) {
                return $this->errorResponse('Impossible de supprimer un compte avec un solde positif', 400);
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
            // Vérifier les permissions
            if (!auth()->user()->isAdmin() && $compte->utilisateur_id !== auth()->id()) {
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
            // Vérifier les permissions
            if (!auth()->user()->isAdmin() && $compte->utilisateur_id !== auth()->id()) {
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
