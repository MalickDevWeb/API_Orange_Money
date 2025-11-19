<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Interfaces
use App\Interfaces\Repositories\{
    CompteRepositoryInterface,
    TransactionRepositoryInterface,
};

use App\Interfaces\Services\{
    CompteServiceInterface,
    TransactionServiceInterface,
    UserRightsServiceInterface,
    StatisticsServiceInterface,
    FeeServiceInterface,
    AuditServiceInterface,
    EmailNotificationServiceInterface,
};

use App\Interfaces\Auth\{
    AuthInterfaceRepository,
    AuthInterfaceService,
};

use App\Interfaces\Notifications\{
  TwilioServiceInterface,
  BrevoServiceInterface,
  EmailServiceInterface
};

// Implementations
use App\Repositories\{
    CompteRepository,
    TransactionRepository,
    AuthRepository,
};

use App\Services\{
    CompteService,
    TransactionService,
    AuthService,
    TwilioService,
    BrevoService,
    UserRightsService,
    StatisticsService,
    FeeService,
    AuditService,
    EmailNotificationService
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompteRepositoryInterface::class, CompteRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(CompteServiceInterface::class, CompteService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(AuthInterfaceRepository::class, AuthRepository::class);
        $this->app->bind(AuthInterfaceService::class, AuthService::class);
        $this->app->bind(TwilioServiceInterface::class, TwilioService::class);
        $this->app->bind(BrevoServiceInterface::class, BrevoService::class);
        $this->app->bind(EmailServiceInterface::class, BrevoService::class);
        $this->app->bind(UserRightsServiceInterface::class, UserRightsService::class);
        $this->app->bind(StatisticsServiceInterface::class, StatisticsService::class);
        $this->app->bind(FeeServiceInterface::class, FeeService::class);
        $this->app->bind(AuditServiceInterface::class, AuditService::class);
        $this->app->bind(EmailNotificationServiceInterface::class, EmailNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
