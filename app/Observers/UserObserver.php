<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Compte;
use App\Utils\GeneratesCompteNumber;
use App\Interfaces\Services\EmailNotificationServiceInterface;

class UserObserver
{
    protected EmailNotificationServiceInterface $emailService;

    public function __construct(EmailNotificationServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user)
    {
        // Générer automatiquement le code marchand pour les commerçants
        if ($user->type === 'commercant') {
            $codeMarchand = $this->generateCodeMarchand($user);
            $user->update(['code_marchand' => $codeMarchand]);
        }

        // Vérifier si l'utilisateur a déjà des comptes (éviter la création multiple)
        $existingComptes = $user->comptes()->count();
        if ($existingComptes > 0) {
            return; // Ne pas créer de compte supplémentaire
        }

        // Crée automatiquement le premier compte pour le nouvel utilisateur
        $compteData = [
            'numero_compte' => GeneratesCompteNumber::generateNumeroCompte($user->nom, $user->prenom),
            'titulaire' => $user->nom . ' ' . $user->prenom,
            'nom_compte' => 'compte principal', // Premier compte = compte principal
            'statut' => 'actif', // Le compte principal est toujours actif
            'utilisateur_id' => $user->id,
            'client_id' => $user->id, // Utilise l'ID utilisateur comme client_id
            'type_compte' => 'courant', // Type par défaut
            'devise' => 'XOF', // Devise par défaut
        ];

        // Ajouter le code marchand au compte si c'est un commerçant
        if ($user->type === 'commercant' && $user->code_marchand) {
            $compteData['code_marchand'] = $user->code_marchand;
        }

        $compte = Compte::create($compteData);

        // Envoyer notification email pour la création du compte principal
        try {
            $userData = [
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'type' => $user->type,
                'code_marchand' => $user->code_marchand,
            ];

            $accountData = [
                'nom_compte' => $compte->nom_compte,
                'numero_compte' => $compte->numero_compte,
                'titulaire' => $compte->titulaire,
                'type_compte' => $compte->type_compte,
                'devise' => $compte->devise,
                'statut' => $compte->statut,
                'solde' => $compte->solde,
                'code_marchand' => $compte->code_marchand,
            ];

            $this->emailService->sendNewAccountNotification($userData, $accountData);
        } catch (\Exception $emailException) {
            // Log l'erreur mais ne pas échouer la création du compte
            \Illuminate\Support\Facades\Log::error('Erreur envoi email compte principal: ' . $emailException->getMessage(), [
                'user_id' => $user->id,
                'account_name' => $compte->nom_compte
            ]);
        }
    }

    /**
     * Génère automatiquement le code marchand pour un commerçant
     * Logique : nom_du_marchand + 4_derniers_chiffres_du_téléphone
     * Si collision, ajouter un numéro séquentiel (1, 2, 3, ...)
     */
    private function generateCodeMarchand(User $user): string
    {
        // Prendre le nom en majuscules et supprimer les espaces
        $nomMarchand = strtoupper(str_replace(' ', '', $user->nom));

        // Prendre les 4 derniers chiffres du téléphone
        $derniersChiffres = substr($user->telephone, -4);

        // Code de base : NOM0000
        $baseCode = $nomMarchand . $derniersChiffres;

        // Vérifier l'unicité et ajouter un compteur si nécessaire
        $codeMarchand = $baseCode;
        $counter = 1;

        while (User::where('code_marchand', $codeMarchand)->exists()) {
            $codeMarchand = $baseCode . $counter;
            $counter++;
        }

        return $codeMarchand;
    }


    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Mettre à jour le statut des comptes si le statut de l'utilisateur change
        if ($user->wasChanged('statut')) {
            $user->comptes()->update(['statut' => $user->statut]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
