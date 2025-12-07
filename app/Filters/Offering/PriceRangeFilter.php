<?php

namespace App\Filters\Offering;

use App\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class PriceRangeFilter implements FilterInterface
{
    public function __construct(
        protected ?float $minPrice,
        protected ?float $maxPrice
    ) {
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->minPrice !== null) {
            $query->where('price', '>=', $this->minPrice);
        }

        if ($this->maxPrice !== null) {
            $query->where('price', '<=', $this->maxPrice);
        }

        return $next($query);
    }
}
