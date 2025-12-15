<?php

namespace App\Filters\Booking;

use App\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentBookingDateRangeFilter implements FilterInterface
{
    public function __construct(
        protected ?string $dateFrom,
        protected ?string $dateTo
    ) {
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        if (!empty($this->dateFrom)) {
            $query->where('created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->where('created_at', '<=', $this->dateTo);
        }

        return $next($query);
    }
}
