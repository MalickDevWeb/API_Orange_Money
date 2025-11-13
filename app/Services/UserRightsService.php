<?php

namespace App\Services;

use App\Interfaces\Services\UserRightsServiceInterface;
use App\Models\User;

class UserRightsService implements UserRightsServiceInterface
{
    public function updateUserRights(int $userId, array $rights): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $user->update($rights);
        return true;
    }

    public function banUser(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $user->update(['banned' => true]);
        return true;
    }

    public function unbanUser(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $user->update(['banned' => false]);
        return true;
    }

    public function setUserTax(int $userId, float $taxPercentage): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $user->update(['tax_percentage' => $taxPercentage]);
        return true;
    }
}
