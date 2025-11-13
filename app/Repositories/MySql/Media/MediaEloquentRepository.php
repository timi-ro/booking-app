<?php

namespace App\Repositories\MySql\Media;

use App\Models\Media;
use App\Repositories\Contracts\MediaRepositoryInterface;

class MediaEloquentRepository implements MediaRepositoryInterface
{
    public function create(array $data): array
    {
        return Media::create($data)->toArray();
    }
}
