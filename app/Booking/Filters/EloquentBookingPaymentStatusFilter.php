<?php

namespace App\Booking\Filters;

use App\Shared\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentBookingPaymentStatusFilter implements FilterInterface
{
    public function __construct(protected ?string $paymentStatus) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (! empty($this->paymentStatus)) {
            $query->where('payment_status', $this->paymentStatus);
        }

        return $next($query);
    }
}
