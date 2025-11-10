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

    public  function getById($id) {
        return $this->repo->getById($id);
    }

    public  function create(array $data) {
       return $this->repo->create($data);
    }

    public  function update($id, array $data) {
        return $this->repo->update($id, $data);
    }

    public  function delete($id) {
        return $this->repo->delete($id);
    }
    public function restore($id) {
        return $this->repo->restore($id);
    }

    public function forceDelete($id) {
        return $this->repo->forceDelete($id);
    }
}
