<?php

namespace App\Providers;

use App\Drivers\Contracts\QueueDriverInterface;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Drivers\Queue\LaravelQueueDriver;
use App\Drivers\Storage\LaravelStorageDriver;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\OfferingDayRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use App\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\MySql\Booking\BookingEloquentRepository;
use App\Repositories\MySql\Media\MediaEloquentRepository;
use App\Repositories\MySql\Offering\OfferingEloquentRepository;
use App\Repositories\MySql\OfferingDay\OfferingDayEloquentRepository;
use App\Repositories\MySql\OfferingTimeSlot\OfferingTimeSlotEloquentRepository;
use App\Repositories\MySql\User\UserEloquentRepository;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // repositories
        $this->app->bind(UserRepositoryInterface::class, UserEloquentRepository::class);
        $this->app->bind(OfferingRepositoryInterface::class, OfferingEloquentRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, MediaEloquentRepository::class);
        $this->app->bind(OfferingDayRepositoryInterface::class, OfferingDayEloquentRepository::class);
        $this->app->bind(OfferingTimeSlotRepositoryInterface::class, OfferingTimeSlotEloquentRepository::class);
        $this->app->bind(BookingRepositoryInterface::class, BookingEloquentRepository::class);

        // drivers
        $this->app->bind(StorageDriverInterface::class, LaravelStorageDriver::class);
        $this->app->bind(QueueDriverInterface::class, LaravelQueueDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Event listeners are auto-discovered by Laravel 12
    }
}
