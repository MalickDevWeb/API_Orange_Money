<?php

namespace App\Utils;

trait HasTypeAndStatus
{
    public function hasType(string $type): bool
    {
        return $this->type === strtolower($type);
    }

    public function hasStatus(string $status): bool
    {
        return $this->statut === strtolower($status);
    }

    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'is')) {
            $name = strtolower(substr($method, 2));

            // Vérifie si c’est un status existant
            if (isset($this->statut) && in_array($name, ['actif','inactif','suspendu','en_attente','reussie','echouee'])) {
                return $this->hasStatus($name);
            }

            // Sinon, traite comme type utilisateur
            if (isset($this->type)) {
                return $this->hasType($name);
            }
        }

        return parent::__call($method, $parameters);
    }
}
