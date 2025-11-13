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
            'image' => $data['image'],
            'video' => $data['video'],
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
