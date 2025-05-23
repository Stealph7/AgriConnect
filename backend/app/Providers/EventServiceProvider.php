<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ProductCreated;
use App\Events\ProductUpdated;
use App\Events\TransactionCompleted;
use App\Listeners\LogProductCreated;
use App\Listeners\LogProductUpdated;
use App\Listeners\LogTransactionCompleted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProductCreated::class => [
            LogProductCreated::class,
        ],
        ProductUpdated::class => [
            LogProductUpdated::class,
        ],
        TransactionCompleted::class => [
            LogTransactionCompleted::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}
