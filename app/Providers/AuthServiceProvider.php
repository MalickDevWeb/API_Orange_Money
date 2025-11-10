<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        User::observe(UserObserver::class);

        Passport::useClientModel(\Laravel\Passport\Client::class);
        Passport::usePersonalAccessClientModel(\Laravel\Passport\PersonalAccessClient::class);

          // Optionnel : durée de vie des tokens
        Passport::personalAccessTokensExpireIn(now()->addDays(30));
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
