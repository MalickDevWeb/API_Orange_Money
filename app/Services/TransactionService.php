<?php

namespace App\Services;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\ServiceInterface;

use App\Models\Transaction;

class TransactionService implements ServiceInterface {

    protected TransactionRepositoryInterface $repo;

    public function __construct(TransactionRepositoryInterface $repo) {
        $this->repo = $repo;
    }

    public function getAll(): array {
        return $this->repo->getAll();
    }

    public function getById($id) {
        return $this->repo->getById($id);
    }

    public function create(array $data) {
        return $this->repo->create($data);
    }

    public function update($id, array $data) {
        return $this->repo->update($id, $data);
    }

    public function delete($id) {
        return $this->repo->delete($id);
    }
}
