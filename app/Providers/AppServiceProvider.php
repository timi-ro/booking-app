<?php

namespace App\Providers;

use App\Auth\Repositories\Contracts\UserRepositoryInterface;
use App\Auth\Repositories\Eloquent\UserEloquentRepository;
use App\Booking\Repositories\Contracts\BookingRepositoryInterface;
use App\Booking\Repositories\Eloquent\BookingEloquentRepository;
use App\Media\Repositories\Contracts\MediaRepositoryInterface;
use App\Media\Repositories\Eloquent\MediaEloquentRepository;
use App\Offering\Repositories\Contracts\OfferingDayRepositoryInterface;
use App\Offering\Repositories\Contracts\OfferingRepositoryInterface;
use App\Offering\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;
use App\Offering\Repositories\Eloquent\OfferingDayEloquentRepository;
use App\Offering\Repositories\Eloquent\OfferingEloquentRepository;
use App\Offering\Repositories\Eloquent\OfferingTimeSlotEloquentRepository;
use App\Shared\Drivers\Contracts\QueueDriverInterface;
use App\Shared\Drivers\Contracts\StorageDriverInterface;
use App\Shared\Drivers\Queue\LaravelQueueDriver;
use App\Shared\Drivers\Storage\LaravelStorageDriver;
use Illuminate\Database\Eloquent\Factories\Factory;
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

        // Resolve factory names for models outside App\Models
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });

        // Event listeners are auto-discovered by Laravel 12
    }
}
