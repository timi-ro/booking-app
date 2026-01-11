<?php

namespace App\Filters\Booking;

use App\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentBookingOfferingIdFilter implements FilterInterface
{
    public function __construct(protected ?int $offeringId) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (! empty($this->offeringId)) {
            $query->where('offering_id', $this->offeringId);
        }

        return $next($query);
    }
}
