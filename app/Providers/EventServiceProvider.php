<?php

namespace App\Providers;

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
        
        // Notification Events
        \App\Events\UserCreatedEvent::class => [
            \App\Listeners\SendUserCreatedNotification::class,
        ],
        \App\Events\OrderCreatedEvent::class => [
            \App\Listeners\SendOrderCreatedNotification::class,
        ],
        \App\Events\OrderCreated::class => [
            \App\Listeners\SendOrderCreatedNotification::class,
            \App\Listeners\SendOrderCreatedWhatsApp::class,
        ],
        \App\Events\OrderStatusChangedEvent::class => [
            \App\Listeners\SendOrderStatusChangedNotification::class,
            \App\Listeners\SendOrderStatusWhatsApp::class,
        ],
        \App\Events\OrderStatusUpdated::class => [
            \App\Listeners\SendOrderStatusWhatsApp::class,
        ],
        \App\Events\ProductCreatedEvent::class => [
            \App\Listeners\SendProductCreatedNotification::class,
        ],
        \App\Events\ProductStockLowEvent::class => [
            \App\Listeners\SendProductStockLowNotification::class,
        ],
        \App\Events\ClientCreatedEvent::class => [
            \App\Listeners\SendClientCreatedNotification::class,
        ],
        
        // Email Events
        \App\Events\CompanyRegistered::class => [
            \App\Listeners\SendWelcomeCompanyEmail::class,
        ],
        \App\Events\PlanConfirmed::class => [
            \App\Listeners\SendPlanConfirmationEmail::class,
        ],
        \App\Events\SaleOrderConfirmedEvent::class => [
            \App\Listeners\SendSaleOrderConfirmationEmail::class,
            \App\Listeners\UpdateGoalProgressOnSaleOrder::class,
        ],
        \App\Events\SaleOrderStatusChangedEvent::class => [
            \App\Listeners\UpdateGoalProgressOnSaleOrder::class,
        ],

        // Subscription Events
        \App\Events\SubscriptionActivated::class => [
            \App\Listeners\SendSubscriptionActivatedNotification::class,
        ],
        \App\Events\SubscriptionCancellationRequested::class => [
            \App\Listeners\SendCancellationRequestedNotification::class,
        ],
        \App\Events\SubscriptionDelinquent::class => [
            \App\Listeners\SendDelinquentNotification::class,
        ],
        \App\Events\SubscriptionReactivated::class => [
            \App\Listeners\SendReactivationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
