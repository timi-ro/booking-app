<?php

namespace App\Filters\Offering;

use App\Filters\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentOfferingSearchFilter implements FilterInterface
{
    public function __construct(protected ?string $search) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $next($query);
    }
}
