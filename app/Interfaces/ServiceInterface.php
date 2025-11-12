<?php
namespace  App\Interfaces;

interface ServiceInterface {
    public function getAll(): array;
    public function getById(string $id);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id);
}
