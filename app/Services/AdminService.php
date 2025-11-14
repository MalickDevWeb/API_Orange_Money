<?php

namespace App\Services;

use App\Interfaces\Services\AdminServiceInterface;
use App\Interfaces\Services\UserRightsServiceInterface;
use App\Interfaces\Services\StatisticsServiceInterface;
use App\Interfaces\Services\FeeServiceInterface;
use App\Interfaces\Services\AuditServiceInterface;

class AdminService implements AdminServiceInterface
{
    protected UserRightsServiceInterface $userRightsService;
    protected StatisticsServiceInterface $statisticsService;
    protected FeeServiceInterface $feeService;
    protected AuditServiceInterface $auditService;

    public function __construct(
        UserRightsServiceInterface $userRightsService,
        StatisticsServiceInterface $statisticsService,
        FeeServiceInterface $feeService,
        AuditServiceInterface $auditService
    ) {
        $this->userRightsService = $userRightsService;
        $this->statisticsService = $statisticsService;
        $this->feeService = $feeService;
        $this->auditService = $auditService;
    }

    public function updateUserTransferRights(int $userId, array $rights): bool
    {
        return $this->userRightsService->updateUserRights($userId, $rights);
    }

    public function banUser(int $userId): bool
    {
        return $this->userRightsService->banUser($userId);
    }

    public function unbanUser(int $userId): bool
    {
        return $this->userRightsService->unbanUser($userId);
    }

    public function getDailyStatistics(): array
    {
        return $this->statisticsService->getDailyStatistics();
    }

    public function updateGlobalFees(float $transactionFee, float $merchantPercentage): bool
    {
        return $this->feeService->updateGlobalFees($transactionFee, $merchantPercentage);
    }

    public function setUserTax(int $userId, float $taxPercentage): bool
    {
        return $this->userRightsService->setUserTax($userId, $taxPercentage);
    }

    public function logAdminAction(string $adminId, string $actionType, ?string $targetUserId = null, ?array $details = null): void
    {
        $this->auditService->logAction($adminId, $actionType, $targetUserId, $details);
    }
}
