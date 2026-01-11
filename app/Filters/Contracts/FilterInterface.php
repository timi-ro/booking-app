<?php

namespace App\Filters\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    /**
     * Apply filter to the query
     */
    public function handle(Builder $query, Closure $next): Builder;
}
