<?php

namespace App\Providers;

use App\Drivers\Contracts\QueueDriverInterface;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Drivers\Queue\LaravelQueueDriver;
use App\Drivers\Storage\LaravelStorageDriver;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\MySql\Media\MediaEloquentRepository;
use App\Repositories\MySql\Offering\OfferingEloquentRepository;
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
        //repositories
        $this->app->bind(UserRepositoryInterface::class, UserEloquentRepository::class);
        $this->app->bind(OfferingRepositoryInterface::class, OfferingEloquentRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, MediaEloquentRepository::class);

        //drivers
        $this->app->bind(StorageDriverInterface::class, LaravelStorageDriver::class);
        $this->app->bind(QueueDriverInterface::class, LaravelQueueDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
