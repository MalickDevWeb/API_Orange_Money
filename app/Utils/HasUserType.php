<?php

namespace App\Utils;

trait HasUserType
{
    /**
     * Vérifie si l'utilisateur est d'un certain type
     */
    public function hasType(string $type): bool
    {
        return $this->type === strtolower($type);
    }

    /**
     * Méthode magique pour gérer isAdmin(), isClient(), etc.
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'is')) {
            $role = strtolower(substr($method, 2));
            return $this->hasType($role);
        }

        return parent::__call($method, $parameters);
    }
}

// $user = User::find(1);

// // Vérification simple avec le trait
// if ($user->hasType('admin')) {
//     echo "C'est un admin";
// }

// // Méthodes magiques automatiquement disponibles
// if ($user->isAdmin()) {
//     echo "C'est un admin";
// }

// if ($user->isClient()) {
//     echo "C'est un client";
// }

// if ($user->isFournisseur()) {
//     echo "C'est un fournisseur";
// }
