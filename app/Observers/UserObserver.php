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
        // Crée automatiquement le premier compte pour le nouvel utilisateur
        Compte::create([
            'numero_compte' => GeneratesCompteNumber::generateNumeroCompte($user->nom, $user->prenom),
            'titulaire' => $user->nom . ' ' . $user->prenom,
            'nom_compte' => 'compte principal', // Premier compte = compte principal
            'statut' => $user->statut, // Statut du compte = statut de l'utilisateur
            'utilisateur_id' => $user->id,
            'client_id' => $user->id, // Utilise l'ID utilisateur comme client_id
            'type_compte' => 'courant', // Type par défaut
            'devise' => 'XOF', // Devise par défaut
        ]);
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
