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

// Implementations
use App\Repositories\{
    CompteRepository,
    TransactionRepository,
};

use App\Services\{
    CompteService,
    TransactionService,
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
