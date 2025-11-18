<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Compte;
use App\Utils\GeneratesCompteNumber;
use App\Services\EmailNotificationService;

class UserObserver
{
    protected EmailNotificationService $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user)
    {
        // Crée automatiquement le premier compte pour le nouvel utilisateur
        $compte = Compte::create([
            'numero_compte' => GeneratesCompteNumber::generateNumeroCompte($user->nom, $user->prenom),
            'titulaire' => $user->nom . ' ' . $user->prenom,
            'nom_compte' => 'compte principal', // Premier compte = compte principal
            'statut' => 'actif', // Premier compte = actif
            'utilisateur_id' => $user->id,
            'client_id' => $user->id, // Utilise l'ID utilisateur comme client_id
            'type_compte' => 'courant', // Type par défaut
            'devise' => 'XOF', // Devise par défaut
        ]);

        // Envoyer email de bienvenue avec informations du compte et QR code
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
                'type_compte' => $compte->type_compte,
                'devise' => $compte->devise,
                'statut' => $compte->statut,
                'solde' => $compte->solde,
                'qr_code' => $compte->qr_code,
            ];

            $this->emailService->sendNewAccountNotification($userData, $accountData);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas échouer la création
            \Illuminate\Support\Facades\Log::error('Erreur envoi email compte principal: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'account_name' => $compte->nom_compte
            ]);
        }
    }


    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
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
