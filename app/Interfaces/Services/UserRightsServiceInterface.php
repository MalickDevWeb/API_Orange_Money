<?php

namespace App\Interfaces\Services;

interface UserRightsServiceInterface
{
    public function updateUserRights(int $userId, array $rights): bool;
    public function banUser(int $userId): bool;
    public function unbanUser(int $userId): bool;
    public function setUserTax(int $userId, float $taxPercentage): bool;
}
