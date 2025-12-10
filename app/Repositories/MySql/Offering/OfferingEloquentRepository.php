<?php

namespace App\Repositories\MySql\Offering;

use App\Filters\Offering\EloquentOfferingPriceRangeFilter;
use App\Filters\Offering\EloquentOfferingSearchFilter;
use App\Filters\Shared\SortFilter;
use App\Models\Offering;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use Illuminate\Pipeline\Pipeline;

class OfferingEloquentRepository implements OfferingRepositoryInterface
{
    public function create(array $data): array
    {
        $offering = Offering::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'address_info' => $data['address_info'],
            'user_id' => $data['user_id'],
        ]);

        return $offering->toArray();
    }

    public function index(int $userId, int $page, int $pageSize): array
    {
        $query = Offering::where('user_id', $userId);

        $offerings = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'data' => $offerings->items(),
            'current_page' => $offerings->currentPage(),
            'per_page' => $offerings->perPage(),
            'total' => $offerings->total(),
            'last_page' => $offerings->lastPage(),
        ];
    }

    public function listAllWithFilters(array $filters, int $page, int $pageSize): array
    {
        $query = app(Pipeline::class)
            ->send(Offering::query())
            ->through([
                new EloquentOfferingSearchFilter($filters['search'] ?? null),
                new EloquentOfferingPriceRangeFilter(
                    $filters['min_price'] ?? null,
                    $filters['max_price'] ?? null
                ),
                new SortFilter(
                    $filters['sort_by'] ?? 'created_at',
                    $filters['sort_direction'] ?? 'desc',
                    ['created_at', 'price', 'title', 'updated_at']
                ),
            ])
            ->thenReturn();

        $offerings = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'data' => $offerings->items(),
            'current_page' => $offerings->currentPage(),
            'per_page' => $offerings->perPage(),
            'total' => $offerings->total(),
            'last_page' => $offerings->lastPage(),
        ];
    }

    public function findByIdWithRelations(int $id): ?array
    {
        $offering = Offering::with(['media', 'availabilities'])
            ->find($id);

        return $offering ? $offering->toArray() : null;
    }

    public function update(int $id, array $data): void
    {
         Offering::where(['id' => $id])->update($data);
    }

    public function delete(int $id): void
    {
        Offering::destroy($id);
    }

    public function findWhere(array $where): array
    {
        $offering = Offering::where($where)->first();

        return $offering ? $offering->toArray() : [];
    }
}
