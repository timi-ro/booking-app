<?php

namespace App\Shared\Filters;

use App\Shared\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class SortFilter implements FilterInterface
{
    public function __construct(
        protected string $sortBy = 'created_at',
        protected string $sortDirection = 'desc',
        protected array $allowedFields = ['created_at']
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (in_array($this->sortBy, $this->allowedFields)) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $next($query);
    }
}
