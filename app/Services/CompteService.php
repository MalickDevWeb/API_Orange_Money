<?php

namespace App\Services;
use App\Interfaces\Services\CompteServiceInterface;
use App\Interfaces\Repositories\CompteRepositoryInterface;

use App\Models\Compte;

class CompteService implements CompteServiceInterface {

    protected CompteRepositoryInterface $repo;
    public function __construct(CompteRepositoryInterface $repo) {
      $this->repo = $repo;
    }
    public  function getAll(): array {
       return $this->repo->getAll();
    }

    public function getById(string $id) {
        return $this->repo->getById($id);
    }

    public function create(array $data) {
        $compte = $this->repo->create($data);
        if ($compte && isset($data['statut']) && $data['statut'] === 'actif') {
            // Désactiver les autres comptes de l'utilisateur
            Compte::where('utilisateur_id', $data['utilisateur_id'])
                  ->where('id', '!=', $compte->id)
                  ->update(['statut' => 'inactif']);
        }
        return $compte;
    }

    public function update(string $id, array $data) {
        $compte = $this->repo->getById($id);
        if (isset($data['statut']) && $data['statut'] === 'actif') {
            // Désactiver les autres comptes de l'utilisateur
            Compte::where('utilisateur_id', $compte->utilisateur_id)
                  ->where('id', '!=', $id)
                  ->update(['statut' => 'inactif']);
        }
        return $this->repo->update($id, $data);
    }

    public function delete(string $id) {
        return $this->repo->delete($id);
    }
    public function restore(string $id) {
        return $this->repo->restore($id);
    }

    public function forceDelete(string $id) {
        return $this->repo->forceDelete($id);
    }
}
