<?php

namespace App\Filters\Booking;

use App\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentBookingUserFilter implements FilterInterface
{
    public function __construct(protected ?int $userId) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (! empty($this->userId)) {
            $query->where('user_id', $this->userId);
        }

        return $next($query);
    }
}
