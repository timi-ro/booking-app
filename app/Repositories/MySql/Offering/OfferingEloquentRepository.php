<?php

namespace App\Repositories\MySql\Offering;

use App\Models\Offering;
use App\Repositories\Contracts\OfferingRepositoryInterface;

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
        $query = Offering::query();

        // Search filter (title or description)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Price range filters
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

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
