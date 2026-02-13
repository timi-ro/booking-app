<?php

namespace App\Booking\Filters;

use App\Shared\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentBookingStatusFilter implements FilterInterface
{
    public function __construct(protected ?string $status) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (! empty($this->status)) {
            $query->where('status', $this->status);
        }

        return $next($query);
    }
}
