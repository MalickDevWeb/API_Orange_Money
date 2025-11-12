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
};

use App\Interfaces\Auth\{
    AuthInterfaceRepository,
    AuthInterfaceService,
};

use App\Interfaces\Notifications\{
  TwilioServiceInterface,
  SendGridServiceInterface
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
    BrevoService
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
        $this->app->bind(SendGridServiceInterface::class, BrevoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
