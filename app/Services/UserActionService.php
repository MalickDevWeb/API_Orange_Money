<?php

namespace App\Services;

use App\Interfaces\Services\UserActionServiceInterface;
use App\Interfaces\Services\UserActionInterface;
use App\Interfaces\Repositories\AdminRepositoryInterface;
use App\Models\User;
use App\Exceptions\Admin\UserNotFoundException;
use App\Exceptions\Admin\InvalidUserActionException;

class UserActionService implements UserActionServiceInterface
{
    protected AdminRepositoryInterface $adminRepository;
    protected array $actions = [];

    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;

        // Register available actions
        $this->registerActions();
    }

    /**
     * Execute user action
     */
    public function executeAction(string $telephone, string $action, array $data = []): array
    {
        $user = $this->adminRepository->findUserByTelephone($telephone);

        if (!$user) {
            throw new UserNotFoundException($telephone);
        }

        if (!isset($this->actions[$action])) {
            throw new InvalidUserActionException($action);
        }

        $actionInstance = $this->actions[$action];

        if (!$actionInstance->isValidForUserType($user->type)) {
            throw new InvalidUserActionException($action, "Action non valide pour le type d'utilisateur {$user->type}");
        }

        return $actionInstance->execute($user, $data);
    }

    /**
     * Check if action is valid for user type
     */
    public function isValidActionForUser(string $action, string $userType): bool
    {
        if (!isset($this->actions[$action])) {
            return false;
        }

        return $this->actions[$action]->isValidForUserType($userType);
    }

    /**
     * Register available user actions
     */
    private function registerActions(): void
    {
        $this->actions['approve'] = new \App\Services\Admin\Actions\ApproveUserAction();
        $this->actions['reject'] = new \App\Services\Admin\Actions\RejectUserAction();
        $this->actions['suspend'] = new \App\Services\Admin\Actions\SuspendUserAction();
        $this->actions['unsuspend'] = new \App\Services\Admin\Actions\UnsuspendUserAction();
        $this->actions['ban'] = new \App\Services\Admin\Actions\BanUserAction();
        $this->actions['unban'] = new \App\Services\Admin\Actions\UnbanUserAction();
        $this->actions['delete'] = new \App\Services\Admin\Actions\DeleteUserAction();
        $this->actions['deposit'] = new \App\Services\Admin\Actions\DepositUserAction();
    }
}
