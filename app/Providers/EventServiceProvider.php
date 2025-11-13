<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Compte;
use App\Observers\UserObserver;
use App\Observers\CompteObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\UserLoggedIn::class => [
            \App\Listeners\SendOtpSmsListener::class,
        ],
        \App\Events\OtpRequested::class => [
            \App\Listeners\SendOtpEmailListener::class,
        ],
        \App\Events\TransactionCreated::class => [
            \App\Listeners\SendTransactionEmailListener::class,
        ],
        \App\Events\NotificationTestRequested::class => [
            \App\Listeners\HandleNotificationTest::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Compte::observe(CompteObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
