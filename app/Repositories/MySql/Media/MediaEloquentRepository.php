<?php

namespace App\Repositories\MySql\Media;

use App\Models\Media;
use App\Models\Offering;
use App\Repositories\Contracts\MediaRepositoryInterface;

class MediaEloquentRepository implements MediaRepositoryInterface
{
    public function create(array $data): array
    {
        return Media::create($data)->toArray();
    }

    public function update(int $id, array $data): void
    {
        Media::where(['id' => $id])->update($data);
    }
}
