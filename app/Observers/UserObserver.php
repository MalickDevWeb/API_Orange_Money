<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Compte;
use App\Utils\GeneratesCompteNumber;

class UserObserver

{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user)
    {
        // Crée automatiquement un compte pour le nouvel utilisateur
        Compte::create([
            'numero_compte' => GeneratesCompteNumber::generateNumeroCompte($user->nom, $user->prenom),
            'titulaire' => $user->nom . ' ' . $user->prenom,
            'statut' => 'actif',
            'utilisateur_id' => $user->id,
        ]);
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
