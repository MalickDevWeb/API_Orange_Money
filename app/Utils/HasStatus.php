<?php

namespace App\Utils;

trait HasStatus
{
    /**
     * Vérifie si le modèle a un certain statut
     */
    public function hasStatut(string $statut): bool
    {
        return strtolower($this->statut) === strtolower($statut);
    }

    /**
     * Méthode magique pour gérer isActif(), isInactif(), isSuspendu(), etc.
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'is')) {
            $status = strtolower(substr($method, 2)); // ex: Actif, Inactif
            return $this->hasStatut($status);
        }

        return parent::__call($method, $parameters);
    }
}
