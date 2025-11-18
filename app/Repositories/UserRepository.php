<?php

namespace App\Repositories;

use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users
     */
    public function getAll(): array
    {
        return User::all()->toArray();
    }

    /**
     * Get user by ID
     */
    public function getById($id)
    {
        return User::find($id);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find user by telephone
     */
    public function findByTelephone(string $telephone): ?User
    {
        return User::where('telephone', $telephone)->first();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Get all users with pagination
     */
    public function getAllPaginated(int $perPage = 15)
    {
        return User::paginate($perPage);
    }

    /**
     * Create new user
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update user
     */
    public function update($id, array $data): bool
    {
        return User::where('id', $id)->update($data) > 0;
    }

    /**
     * Delete user
     */
    public function delete($id): bool
    {
        return User::where('id', $id)->delete() > 0;
    }

    /**
     * Get user accounts
     */
    public function getUserAccounts(int $userId): Collection
    {
        return User::find($userId)?->comptes ?? collect();
    }

    /**
     * Get user active account
     */
    public function getUserActiveAccount(int $userId): ?User
    {
        return User::with(['comptes' => function($query) {
            $query->where('statut', 'actif');
        }])->find($userId);
    }
}
