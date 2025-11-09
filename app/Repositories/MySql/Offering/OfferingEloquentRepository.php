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
        ]);

        return $offering->toArray();
    }
}
